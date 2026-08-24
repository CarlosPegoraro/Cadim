<?php

namespace App\Livewire;

use App\Services\TransactionImportService;
use App\Services\TransactionService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TransactionsPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public bool $showForm = false;

    public array $form = [];

    public array $editForm = [];

    public bool $showEditForm = false;

    public ?int $editingId = null;

    public string $editScope = 'occurrence';

    public bool $editingSeriesAvailable = false;

    public string $successMessage = '';

    public bool $showImportForm = false;

    public $importFile;

    public array $importHeaders = [];

    public array $importRows = [];

    public array $importMapping = [];

    public array $importFields = [];

    protected function messages(): array
    {
        return [
            'form.amount.required' => 'Informe o valor da transação.',
            'form.amount.numeric' => 'Informe um valor numérico válido.',
            'form.amount.min' => 'O valor deve ser maior que zero.',
            'form.description.required' => 'Informe uma descrição.',
            'form.due_date.required' => 'Informe uma data.',
            'editForm.amount.required' => 'Informe o valor da transação.',
            'editForm.amount.numeric' => 'Informe um valor numérico válido.',
            'editForm.amount.min' => 'O valor deve ser maior que zero.',
        ];
    }

    public function mount()
    {
        $this->search = $this->search ?: request()->string('search')->toString();
        $this->dateFrom = $this->dateFrom ?: now()->startOfMonth()->toDateString();
        $this->dateTo = $this->dateTo ?: now()->endOfMonth()->toDateString();
        $this->resetForm();
        $this->importFields = TransactionImportService::FIELDS;
    }

    public function resetForm()
    {
        $this->form = ['type' => 'expense', 'amount' => '', 'description' => '', 'merchant' => '', 'category_id' => '', 'financial_account_id' => '', 'credit_card_id' => '', 'due_date' => now()->toDateString(), 'recurrence' => 'one_time', 'installments' => '', 'notes' => ''];
    }

    public function openForm()
    {
        $this->resetForm();
        $this->resetValidation();
        $this->successMessage = '';
        $this->showForm = true;
    }

    public function save(TransactionService $service)
    {
        $d = $this->validate($this->transactionRules('form'));
        $f = $d['form'];
        foreach (['category_id', 'financial_account_id', 'credit_card_id'] as $field) {
            $f[$field] = filled($f[$field]) ? (int) $f[$field] : null;
        } if ($f['category_id'] && ! auth()->user()->categories()->whereKey($f['category_id'])->exists()) {
            abort(403);
        } if ($f['financial_account_id'] && ! auth()->user()->accounts()->whereKey($f['financial_account_id'])->exists()) {
            abort(403);
        } if ($f['credit_card_id'] && ! auth()->user()->creditCards()->whereKey($f['credit_card_id'])->exists()) {
            abort(403);
        }

        $f['purchase_date'] = $f['due_date'];
        $service->create(auth()->user(), $f);
        $this->showForm = false;
        $this->successMessage = 'Lançamento criado com sucesso.';
    }

    public function openEdit($id)
    {
        $transaction = $this->transaction($id);
        $this->editingId = $transaction->id;
        $this->editScope = 'occurrence';
        $this->editingSeriesAvailable = (bool) $transaction->series && $transaction->series->recurrence !== 'one_time';
        $this->editForm = [
            'type' => $transaction->type,
            'amount' => (string) $transaction->amount,
            'description' => $transaction->description,
            'merchant' => $transaction->merchant ?? '',
            'category_id' => (string) ($transaction->category_id ?? ''),
            'financial_account_id' => (string) ($transaction->financial_account_id ?? ''),
            'credit_card_id' => (string) ($transaction->credit_card_id ?? ''),
            'purchase_date' => ($transaction->purchase_date ?? $transaction->due_date)->toDateString(),
            'due_date' => $transaction->due_date->toDateString(),
            'status' => $transaction->status,
            'settled_at' => $transaction->settled_at?->toDateString() ?? '',
            'notes' => $transaction->notes ?? '',
        ];
        $this->showEditForm = true;
        $this->resetValidation();
    }

    public function saveEdit(TransactionService $service)
    {
        $rules = [
            'editForm.type' => 'required|in:income,expense',
            'editForm.amount' => 'required|numeric|min:0.01',
            'editForm.description' => 'required|max:255',
            'editForm.merchant' => 'nullable|max:255',
            ...$this->transactionRules('editForm'),
            'editForm.purchase_date' => 'required|date',
            'editForm.notes' => 'nullable|max:2000',
        ];

        if ($this->editScope === 'occurrence') {
            $rules += [
                'editForm.due_date' => 'required|date',
                'editForm.status' => 'required|in:pending,settled,canceled',
                'editForm.settled_at' => 'nullable|date',
            ];
        }

        $data = $this->validate($rules)['editForm'];
        foreach (['category_id', 'financial_account_id', 'credit_card_id'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? (int) $data[$field] : null;
        }

        $transaction = $this->transaction($this->editingId);
        $this->assertOwnedReferences($data);

        if ($this->editScope === 'series' && $transaction->series) {
            $service->updateSeries($transaction, $data);
        } else {
            $service->updateOccurrence($transaction, $data);
        }

        $this->closeEdit();
        $this->successMessage = 'Lançamento atualizado com sucesso.';
    }

    public function duplicate($id, TransactionService $service)
    {
        $service->duplicate($this->transaction($id));
        $this->successMessage = 'Lançamento duplicado com sucesso.';
    }

    public function settle($id, TransactionService $service)
    {
        $service->toggleSettled($this->transaction($id));
        $this->successMessage = 'Status do lançamento atualizado.';
    }

    public function cancel($id, TransactionService $service)
    {
        $service->cancel($this->transaction($id));
        $this->successMessage = 'Lançamento cancelado.';
    }

    public function cancelFuture($id, TransactionService $service)
    {
        $service->cancelFuture($this->transaction($id));
        $this->successMessage = 'Série interrompida a partir deste lançamento.';
    }

    public function delete($id, TransactionService $service)
    {
        $service->deleteOccurrence($this->transaction($id));
        $this->successMessage = 'Lançamento excluído.';
    }

    public function closeEdit(): void
    {
        $this->showEditForm = false;
        $this->editingId = null;
        $this->editForm = [];
        $this->editingSeriesAvailable = false;
    }

    public function setPeriod(string $period): void
    {
        $range = match ($period) {
            'today' => [today(), today()],
            'next_month' => [now()->addMonthNoOverflow()->startOfMonth(), now()->addMonthNoOverflow()->endOfMonth()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
        [$this->dateFrom, $this->dateTo] = array_map(fn ($date) => $date->toDateString(), $range);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->type = '';
        $this->status = '';
        $this->search = '';
        $this->setPeriod('month');
    }

    public function openImport(): void
    {
        $this->resetImport();
        $this->resetValidation();
        $this->showImportForm = true;
    }

    public function updatedImportFile(TransactionImportService $service): void
    {
        if (! $this->importFile) {
            return;
        }

        $this->validate(['importFile' => 'required|file|mimes:csv,txt|max:5120']);
        $preview = $service->preview($this->importFile);
        $this->importHeaders = $preview['headers'];
        $this->importRows = $preview['rows'];
        $this->importMapping = $preview['mapping'];
    }

    public function confirmImport(TransactionImportService $service): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:5120',
            'importMapping.amount' => 'required',
            'importMapping.description' => 'required',
            'importMapping.due_date' => 'required',
        ]);
        if ($this->importRows === []) {
            $this->addError('importFile', 'Selecione um CSV com pelo menos uma linha.');

            return;
        }

        $created = $service->import(auth()->user(), $this->importHeaders, $this->importRows, $this->importMapping);
        $duplicates = $service->duplicatesSkipped;
        $this->resetImport();
        $this->showImportForm = false;
        $this->successMessage = "{$created} lançamento(s) importado(s) com sucesso.".($duplicates > 0 ? " {$duplicates} duplicado(s) foram ignorado(s)." : '');
    }

    public function resetImport(): void
    {
        $this->importFile = null;
        $this->importHeaders = [];
        $this->importRows = [];
        $this->importMapping = [];
    }

    private function transaction($id)
    {
        return auth()->user()->transactions()->with('series')->findOrFail($id);
    }

    private function assertOwnedReferences(array $data): void
    {
        foreach (['category_id' => 'categories', 'financial_account_id' => 'accounts', 'credit_card_id' => 'creditCards'] as $field => $relation) {
            if ($data[$field] ?? null) {
                if (! auth()->user()->{$relation}()->whereKey($data[$field])->exists()) {
                    abort(403);
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function transactionRules(string $prefix): array
    {
        return [
            "$prefix.type" => 'required|in:income,expense',
            "$prefix.amount" => 'required|numeric|min:0.01',
            "$prefix.description" => 'required|max:255',
            "$prefix.merchant" => 'nullable|max:255',
            "$prefix.category_id" => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())->where('type', data_get($this, "$prefix.type"))),
            ],
            "$prefix.financial_account_id" => [
                'nullable',
                Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            "$prefix.credit_card_id" => [
                'nullable',
                Rule::exists('credit_cards', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            "$prefix.notes" => 'nullable|max:2000',
        ] + ($prefix === 'form'
            ? [
                "$prefix.due_date" => 'required|date',
                "$prefix.recurrence" => 'required|in:one_time,monthly,installment',
                "$prefix.installments" => 'nullable|required_if:form.recurrence,installment|integer|min:2|max:360',
            ]
            : []);
    }

    public function updated($field)
    {
        if (in_array($field, ['type', 'status', 'search', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $q = auth()->user()->transactions()->with(['category', 'account', 'creditCard'])->orderByDesc('due_date');
        if ($this->type) {
            $q->where('type', $this->type);
        } if ($this->status) {
            $q->where('status', $this->status);
        } if ($this->search) {
            $q->where(fn ($x) => $x->where('description', 'ilike', '%'.$this->search.'%')->orWhere('merchant', 'ilike', '%'.$this->search.'%'));
        } if ($this->dateFrom) {
            $q->whereDate('due_date', '>=', $this->dateFrom);
        } if ($this->dateTo) {
            $q->whereDate('due_date', '<=', $this->dateTo);
        }

        return view('livewire.transactions-page', ['transactions' => $q->paginate(12), 'categories' => auth()->user()->categories()->where('is_archived', false)->orderBy('name')->get(), 'accounts' => auth()->user()->accounts()->where('is_archived', false)->get(), 'cards' => auth()->user()->creditCards()->where('is_archived', false)->get()])->layout('layouts.app');
    }
}
