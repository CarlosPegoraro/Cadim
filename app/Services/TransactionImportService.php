<?php

namespace App\Services;

use App\Models\TransactionOccurrence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionImportService
{
    public const MAX_ROWS = 500;

    /** @var array<string, string> */
    public const FIELDS = [
        'type' => 'Tipo',
        'amount' => 'Valor',
        'description' => 'Descrição',
        'merchant' => 'Loja / pagador',
        'due_date' => 'Data prevista',
        'purchase_date' => 'Data da compra',
        'status' => 'Status',
        'category' => 'Categoria',
        'account' => 'Conta',
        'card' => 'Cartão',
        'notes' => 'Observações',
    ];

    public int $duplicatesSkipped = 0;

    /** @return array{headers: list<string>, rows: list<list<string>>, mapping: array<string, string>} */
    public function preview(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['importFile' => 'Não foi possível ler o arquivo CSV.']);
        }

        $firstLine = (string) fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter);
        if (! is_array($headers) || count($headers) < 2) {
            fclose($handle);
            throw ValidationException::withMessages(['importFile' => 'O CSV precisa ter pelo menos duas colunas.']);
        }

        $headers = array_map(fn ($header): string => trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && count($rows) < self::MAX_ROWS) {
            if (count(array_filter($row, fn ($value): bool => filled($value))) === 0) {
                continue;
            }
            $rows[] = array_pad(array_slice($row, 0, count($headers)), count($headers), '');
        }
        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows, 'mapping' => $this->suggestMapping($headers)];
    }

    /** @param list<string> $headers @param list<list<string>> $rows @param array<string, string> $mapping */
    public function import(User $user, array $headers, array $rows, array $mapping): int
    {
        $errors = [];
        $created = 0;
        $this->duplicatesSkipped = 0;

        DB::transaction(function () use ($user, $headers, $rows, $mapping, &$errors, &$created): void {
            foreach ($rows as $index => $row) {
                try {
                    $data = $this->mapRow($user, $headers, $row, $mapping);
                    if ($this->isDuplicate($user, $data)) {
                        $this->duplicatesSkipped++;

                        continue;
                    }
                    $series = app(TransactionService::class)->create($user, $data);
                    $occurrence = $series->occurrences()->firstOrFail();
                    $this->applyStatus($occurrence, $data['status'] ?? 'pending');
                    $created++;
                } catch (ValidationException $exception) {
                    $errors[] = 'Linha '.($index + 2).': '.collect($exception->errors())->flatten()->first();
                } catch (\Throwable $exception) {
                    $errors[] = 'Linha '.($index + 2).': '.$exception->getMessage();
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages(['importFile' => $errors]);
            }
        });

        return $created;
    }

    /** @param array<string, mixed> $data */
    private function isDuplicate(User $user, array $data): bool
    {
        return $user->transactions()
            ->where('type', $data['type'])
            ->where('amount', $data['amount'])
            ->whereDate('due_date', $data['due_date'])
            ->where('description', $data['description'])
            ->when($data['merchant'] ?? null, fn (Builder $query, string $merchant) => $query->where('merchant', $merchant), fn (Builder $query) => $query->whereNull('merchant'))
            ->where('status', '!=', 'canceled')
            ->exists();
    }

    /** @param list<string> $headers @return array<string, string> */
    private function suggestMapping(array $headers): array
    {
        $normalized = array_combine(array_map([$this, 'normalizeHeader'], $headers), $headers);
        $aliases = [
            'type' => ['type', 'tipo', 'natureza'],
            'amount' => ['amount', 'valor', 'value', 'preco', 'preço'],
            'description' => ['description', 'descricao', 'descrição', 'lancamento', 'lançamento', 'historico', 'histórico'],
            'merchant' => ['merchant', 'loja', 'estabelecimento', 'pagador', 'beneficiario', 'beneficiário'],
            'due_date' => ['due_date', 'data', 'data_prevista', 'vencimento', 'date'],
            'purchase_date' => ['purchase_date', 'data_compra', 'compra'],
            'status' => ['status', 'situacao', 'situação'],
            'category' => ['category', 'categoria'],
            'account' => ['account', 'conta'],
            'card' => ['card', 'cartao', 'cartão'],
            'notes' => ['notes', 'observacoes', 'observações', 'notas'],
        ];

        return collect(self::FIELDS)->keys()->mapWithKeys(function (string $field) use ($normalized, $aliases): array {
            $header = collect($aliases[$field] ?? [$field])->map(fn ($alias) => $this->normalizeHeader($alias))->first(fn ($alias) => isset($normalized[$alias]));

            return [$field => $header ? $normalized[$header] : ''];
        })->all();
    }

    /** @param list<string> $headers @param list<string> $row @param array<string, string> $mapping @return array<string, mixed> */
    private function mapRow(User $user, array $headers, array $row, array $mapping): array
    {
        $value = fn (string $field): string => $this->mappedValue($row, $mapping[$field] ?? '', $headers);
        $description = trim($value('description'));
        $amount = $this->parseAmount($value('amount'));
        $dueDate = $this->parseDate($value('due_date'));
        if ($description === '' || $amount === null || $dueDate === null) {
            throw ValidationException::withMessages(['importFile' => 'Descrição, valor e data prevista são obrigatórios.']);
        }

        $type = strtolower(trim($value('type') ?: 'expense'));
        $type = match ($type) {
            'income', 'entrada', 'receita' => 'income',
            'expense', 'saida', 'saída', 'despesa' => 'expense',
            default => throw ValidationException::withMessages(['importFile' => 'Tipo deve ser entrada/income ou saída/expense.']),
        };

        $categoryId = $this->findOwnedId($user->categories()->where('type', $type)->where('is_archived', false), $value('category'), 'categoria');
        $accountId = $this->findOwnedId($user->accounts()->where('is_archived', false), $value('account'), 'conta');
        $cardId = $this->findOwnedId($user->creditCards()->where('is_archived', false), $value('card'), 'cartão');
        $status = strtolower(trim($value('status') ?: 'pending'));
        $status = match ($status) {
            'pending', 'pendente' => 'pending',
            'settled', 'confirmado', 'confirmada', 'pago', 'paga' => 'settled',
            'canceled', 'cancelado', 'cancelada' => 'canceled',
            default => throw ValidationException::withMessages(['importFile' => 'Status inválido.']),
        };

        return [
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'merchant' => $value('merchant') ?: null,
            'category_id' => $categoryId,
            'financial_account_id' => $accountId,
            'credit_card_id' => $cardId,
            'purchase_date' => $this->parseDate($value('purchase_date'))?->toDateString() ?? $dueDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'recurrence' => 'one_time',
            'installments' => null,
            'notes' => $value('notes') ?: null,
            'status' => $status,
        ];
    }

    /** @param Builder<Model> $query */
    private function findOwnedId($query, string $name, string $label): ?int
    {
        if ($name === '') {
            return null;
        }
        $model = $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();
        if (! $model) {
            throw ValidationException::withMessages(['importFile' => "{$label} não encontrada: {$name}."]);
        }

        return (int) $model->getKey();
    }

    /** @param list<string> $row */
    private function mappedValue(array $row, string $header, array $headers): string
    {
        if ($header === '') {
            return '';
        }
        $index = array_search($header, $headers, true);

        return $index === false ? '' : trim((string) ($row[$index] ?? ''));
    }

    private function parseAmount(string $value): ?float
    {
        $value = preg_replace('/[^0-9,.-]/', '', trim($value)) ?? '';
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.') ? str_replace('.', '', str_replace(',', '.', $value)) : str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->startOfDay();
            } catch (\Throwable) {
                // Try the next supported format.
            }
        }

        return null;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim(str_replace(['-', ' '], '_', $header)));
    }

    private function applyStatus(TransactionOccurrence $occurrence, string $status): void
    {
        if ($status === 'settled') {
            $occurrence->update(['status' => 'settled', 'settled_at' => $occurrence->due_date]);
        } elseif ($status === 'canceled') {
            $occurrence->update(['status' => 'canceled', 'settled_at' => null]);
        }
    }
}
