<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletMovement;
use App\Services\CashboxService;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CashboxController extends Controller
{
    private $cashboxes;
    private $currencies;

    public function __construct(CashboxService $cashboxes, CurrencyService $currencies)
    {
        $this->cashboxes = $cashboxes;
        $this->currencies = $currencies;
    }

    public function index(): Response
    {
        $wallets = $this->cashboxes->balancesForUser((int) auth()->id());

        $exchangeRate = null;
        if ((int) auth()->user()->country_id === Country::SYRIA) {
            try {
                $exchangeRate = $this->currencies->rate('SYP');
            } catch (InvalidArgumentException $exception) {
                $exchangeRate = null;
            }
        }

        return Inertia::render('Admin/Cashboxes/Index', [
            'wallets' => $wallets,
            'walletOwnerId' => (int) auth()->id(),
            'movements' => WalletMovement::query()
                ->where('user_id', auth()->id())
                ->latest('id')
                ->paginate(30),
            'canExchange' => (int) auth()->user()->country_id === Country::SYRIA,
            'exchangeRate' => $exchangeRate,
            'defaultCurrency' => Country::defaultCurrency((int) auth()->user()->country_id),
        ]);
    }

    public function exchange(Request $request)
    {
        abort_unless((int) auth()->user()->country_id === Country::SYRIA, 403);
        $data = $request->validate([
            'from' => 'required|in:USD,SYP',
            'to' => 'required|in:USD,SYP|different:from',
            'amount' => 'required|numeric|min:0.01',
            'rate' => 'required|numeric|min:0.000001',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->cashboxes->exchange(
                (int) auth()->id(),
                $data['from'],
                $data['to'],
                (float) $data['amount'],
                (float) $data['rate'],
                $data['note'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return Redirect::back()->withErrors(['amount' => $exception->getMessage()]);
        }

        return Redirect::route('cashboxes.index')->with('success', 'تم تسجيل عملية الصرف بنجاح.');
    }
}
