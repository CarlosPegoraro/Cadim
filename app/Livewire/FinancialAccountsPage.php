<?php

namespace App\Livewire;

use App\Services\AccountBalanceService;
use App\Services\CardNumberDetector;
use App\Services\CreditCardBalanceService;
use App\Services\TransactionService;
use App\Services\TransferService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FinancialAccountsPage extends Component
{
    public array $account = ['name' => '', 'type' => 'checking', 'initial_balance' => ''];

    public string $activeTab = 'accounts';

    public bool $accountModalOpen = false;

    public bool $cardModalOpen = false;

    public bool $transferModalOpen = false;

    public ?int $editingAccountId = null;

    public ?int $editingCardId = null;

    public array $card = ['name' => '', 'number' => '', 'brand' => '', 'bin' => '', 'issuer' => '', 'country' => '', 'card_type' => '', 'financial_account_id' => '', 'closing_day' => '', 'due_day' => '', 'limit' => ''];

    public array $cardLookup = [];

    public string $binLookupMessage = '';

    public array $transfer = ['from_account_id' => '', 'to_account_id' => '', 'amount' => '', 'transfer_date' => '', 'description' => '', 'status' => 'pending'];

    public string $successMessage = '';

    public function mount(): void
    {
        $this->transfer['transfer_date'] = now()->toDateString();
        $this->activeTab = in_array(request()->query('tab'), ['accounts', 'cards'], true)
            ? request()->query('tab')
            : 'accounts';
    }

    public function saveAccount(TransactionService $transactions)
    {
        $d = $this->validate(['account.name' => 'required|max:100', 'account.type' => 'required|in:checking,investments,cash', 'account.initial_balance' => 'nullable|numeric']);
        if ($this->editingAccountId) {
            auth()->user()->accounts()->whereKey($this->editingAccountId)->firstOrFail()->update($d['account']);
            $message = 'Conta atualizada com sucesso.';
        } else {
            $initialBalance = (float) ($d['account']['initial_balance'] ?? 0);
            DB::transaction(function () use ($d, $initialBalance, $transactions): void {
                // O saldo de abertura passa a existir como lançamento, evitando que seja somado duas vezes.
                $account = auth()->user()->accounts()->create([...$d['account'], 'initial_balance' => 0]);
                $transactions->createOpeningBalance(auth()->user(), $account, $initialBalance);
            });
            $message = 'Conta adicionada com sucesso.';
        }
        $this->account = ['name' => '', 'type' => 'checking', 'initial_balance' => ''];
        $this->editingAccountId = null;
        $this->accountModalOpen = false;
        $this->successMessage = $message;
    }

    public function editAccount(int $id): void
    {
        $account = auth()->user()->accounts()->whereKey($id)->firstOrFail();
        $this->editingAccountId = $account->id;
        $this->account = ['name' => $account->name, 'type' => $account->type, 'initial_balance' => (string) $account->initial_balance];
        if ($this->account['type'] === 'savings') {
            $this->account['type'] = 'investments';
        }
        $this->resetValidation();
        $this->accountModalOpen = true;
    }

    public function toggleAccount(int $id): void
    {
        $account = auth()->user()->accounts()->whereKey($id)->firstOrFail();
        $account->update(['is_archived' => ! $account->is_archived]);
        $this->successMessage = $account->is_archived ? 'Conta desabilitada.' : 'Conta reativada.';
    }

    public function deleteAccount(int $id): void
    {
        auth()->user()->accounts()->whereKey($id)->firstOrFail()->delete();
        $this->successMessage = 'Conta excluída permanentemente.';
    }

    public function saveCard()
    {
        $d = $this->validate([
            'card.name' => 'required|max:100',
            'card.number' => 'nullable|digits_between:6,19',
            'card.brand' => 'nullable|max:50',
            'card.bin' => 'nullable|digits_between:6,8',
            'card.issuer' => 'nullable|max:255',
            'card.country' => 'nullable|max:100',
            'card.card_type' => 'nullable|max:20',
            'card.financial_account_id' => [
                'nullable',
                'integer',
                Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            'card.closing_day' => 'required|numeric|between:1,31',
            'card.due_day' => 'required|numeric|between:1,31',
            'card.limit' => 'nullable|numeric|min:0',
        ]);
        unset($d['card']['number']);
        $d['card']['financial_account_id'] = filled($d['card']['financial_account_id'] ?? null)
            ? $d['card']['financial_account_id']
            : null;
        $d['card']['limit'] = filled($d['card']['limit'] ?? null) ? $d['card']['limit'] : null;
        $d['card']['metadata'] = $this->cardLookup ?: null;
        if ($this->editingCardId) {
            auth()->user()->creditCards()->whereKey($this->editingCardId)->firstOrFail()->update($d['card']);
            $message = 'Cartão atualizado com sucesso.';
        } else {
            auth()->user()->creditCards()->create($d['card']);
            $message = 'Cartão adicionado com sucesso.';
        }
        $this->card = ['name' => '', 'number' => '', 'brand' => '', 'bin' => '', 'issuer' => '', 'country' => '', 'card_type' => '', 'financial_account_id' => '', 'closing_day' => '', 'due_day' => '', 'limit' => ''];
        $this->cardLookup = [];
        $this->editingCardId = null;
        $this->cardModalOpen = false;
        $this->successMessage = $message;
    }

    public function editCard(int $id): void
    {
        $card = auth()->user()->creditCards()->whereKey($id)->firstOrFail();
        $this->editingCardId = $card->id;
        $this->card = ['name' => $card->name, 'number' => '', 'brand' => $card->brand ?? '', 'bin' => $card->bin ?? '', 'issuer' => $card->issuer ?? '', 'country' => $card->country ?? '', 'card_type' => $card->card_type ?? '', 'financial_account_id' => (string) ($card->financial_account_id ?? ''), 'closing_day' => (string) $card->closing_day, 'due_day' => (string) $card->due_day, 'limit' => $card->limit === null ? '' : (string) $card->limit];
        $this->cardLookup = $card->metadata ?? [];
        $this->resetValidation();
        $this->cardModalOpen = true;
    }

    public function toggleCard(int $id): void
    {
        $card = auth()->user()->creditCards()->whereKey($id)->firstOrFail();
        $card->update(['is_archived' => ! $card->is_archived]);
        $this->successMessage = $card->is_archived ? 'Cartão desabilitado.' : 'Cartão reativado.';
    }

    public function deleteCard(int $id): void
    {
        auth()->user()->creditCards()->whereKey($id)->firstOrFail()->delete();
        $this->successMessage = 'Cartão excluído permanentemente.';
    }

    public function lookupCardBin(CardNumberDetector $detector): void
    {
        $digits = preg_replace('/\D+/', '', (string) ($this->card['number'] ?? '')) ?? '';
        $this->card['number'] = substr($digits, 0, 19);
        if (strlen($digits) < 6) {
            $this->cardLookup = [];
            $this->card['bin'] = '';
            $this->binLookupMessage = 'Digite pelo menos 6 dígitos para consultar.';
            $this->addError('card.number', $this->binLookupMessage);

            return;
        }

        $this->resetValidation('card.number');
        $this->cardLookup = $detector->detect($digits) ?? [];
        $this->binLookupMessage = $this->cardLookup === []
            ? 'Bandeira não identificada. Preencha os dados do cartão manualmente.'
            : (! $this->cardLookup['is_valid'] ? 'A bandeira foi identificada, mas o número não passou na validação.' : '');
        $this->card['bin'] = substr($digits, 0, 6);
        foreach (['brand', 'issuer', 'country'] as $field) {
            if (filled($this->cardLookup[$field] ?? null)) {
                $this->card[$field] = $this->cardLookup[$field];
            }
        }
        if (filled($this->cardLookup['type'] ?? null)) {
            $this->card['card_type'] = $this->cardLookup['type'];
        }
    }

    public function updatedCardNumber(): void
    {
        $digits = preg_replace('/\D+/', '', (string) ($this->card['number'] ?? '')) ?? '';
        $this->card['number'] = substr($digits, 0, 19);
        $this->cardLookup = [];
        $this->binLookupMessage = '';
        $this->card['bin'] = strlen($digits) >= 6 ? substr($digits, 0, 6) : '';
    }

    public function cardBrandClass(?string $brand): string
    {
        return match (strtolower((string) $brand)) {
            'visa' => 'brand-visa',
            'mastercard' => 'brand-mastercard',
            'american express', 'amex' => 'brand-amex',
            'elo' => 'brand-elo',
            default => 'brand-default',
        };
    }

    public function cardBrandStyle(?string $brand): string
    {
        return match (strtolower((string) $brand)) {
            'visa' => 'background: linear-gradient(135deg, #1d4ed8, #1e1b4b); color: #fff;',
            'mastercard' => 'background: linear-gradient(135deg, #f97316, #881337); color: #fff;',
            'american express', 'amex' => 'background: linear-gradient(135deg, #0891b2, #0f172a); color: #fff;',
            'elo' => 'background: linear-gradient(135deg, #eab308, #9a3412); color: #fff;',
            default => 'background: linear-gradient(135deg, #334155, #020617); color: #fff;',
        };
    }

    public function saveTransfer(TransferService $service): void
    {
        $data = $this->validate([
            'transfer.from_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id()))],
            'transfer.to_account_id' => ['required', 'integer', 'different:transfer.from_account_id', Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id()))],
            'transfer.amount' => 'required|numeric|min:0.01',
            'transfer.transfer_date' => 'required|date',
            'transfer.description' => 'nullable|max:255',
            'transfer.status' => 'required|in:pending,settled',
        ])['transfer'];
        $service->create(auth()->user(), $data);
        $this->transfer = ['from_account_id' => '', 'to_account_id' => '', 'amount' => '', 'transfer_date' => now()->toDateString(), 'description' => '', 'status' => 'pending'];
        $this->transferModalOpen = false;
        $this->successMessage = 'Transferência registrada com sucesso.';
    }

    public function settleTransfer(int $id, TransferService $service): void
    {
        $service->toggleSettled(auth()->user()->transfers()->findOrFail($id));
        $this->successMessage = 'Status da transferência atualizado.';
    }

    public function cancelTransfer(int $id, TransferService $service): void
    {
        $service->cancel(auth()->user()->transfers()->findOrFail($id));
        $this->successMessage = 'Transferência cancelada.';
    }

    public function toggleInvoicePayment(int $cardId, string $invoiceMonth, CreditCardBalanceService $service): void
    {
        $card = auth()->user()->creditCards()->whereKey($cardId)->firstOrFail();
        $month = Carbon::parse($invoiceMonth)->startOfMonth();
        $summary = $service->summarize($card, $month);

        if ($summary['current_invoice_paid']) {
            $service->unpaidInvoice($card, $month);
            $this->successMessage = 'Pagamento da fatura desfeito.';
        } else {
            $service->payInvoice($card, $month, (float) $summary['current_invoice'], now());
            $this->successMessage = 'Fatura marcada como paga.';
        }
    }

    public function render(AccountBalanceService $balances, CreditCardBalanceService $cardBalances)
    {
        $summary = $balances->summarizeForUser(auth()->user());
        $cardSummary = $cardBalances->summarizeForUser(auth()->user());

        return view('livewire.financial-accounts-page', [
            'accounts' => auth()->user()->accounts()->where('is_archived', false)->orderBy('name')->get(),
            'cards' => auth()->user()->creditCards()->with('account')->where('is_archived', false)->get(),
            'transfers' => auth()->user()->transfers()->with(['fromAccount', 'toAccount'])->latest('transfer_date')->latest()->limit(12)->get(),
            'balanceSummaries' => $summary['accounts'],
            'consolidated' => $summary['consolidated'],
            'netWorth' => $summary['net_worth'],
            'cardSummaries' => $cardSummary['cards'],
            'cardConsolidated' => $cardSummary['consolidated'],
        ])->layout('layouts.app');
    }
}
