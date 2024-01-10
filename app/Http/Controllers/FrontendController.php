<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class FrontendController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['galleries'])->latest()->get();
        return view('pages.frontend.index', compact('products'));
    }

    public function details(Request $request, $slug)
    {
        $products = Product::with(['galleries'])->where('slug', $slug)->firstOrFail();
        $recomendations = Product::with(['galleries'])->inRandomOrder()->limit(5)->get();
        return view('pages.frontend.details', compact(['products', 'recomendations']));
    }

    public function cartAdd(Request $request, $id)
    {
        Cart::create([
           'users_id' => Auth::user()->id,
            'products_id' => $id,
        ]);

        return redirect()->route('frontend.cart');

    }

    public function cartDelete(Request $request, $id)
    {
        $item = Cart::findOrFail($id);

        $item->delete();

        return redirect()->route('frontend.cart');
    }

    public function cart(Request $request)
    {
        $carts = Cart::with(['product.galleries'])->where('users_id', Auth::user()->id)->get();
        return view('pages.frontend.cart', compact('carts'));
    }

    public function checkout(CheckoutRequest $request)
    {
        $data = $request->all();

        //Get Cart Data

        $carts = Cart::with(['product'])->where('users_id', Auth::user()->id)->get();

        //Add to transaction data

        $data['users_id'] = Auth::user()->id;
        $data['total_price'] = $carts->sum('product.price');

        //Create Transaction

        $transactions = Transaction::create($data);

        //Create Transaction Item

        foreach ($carts as $cart)
        {
            $items[] = TransactionItem::create([
               'transactions_id' => $transactions->id,
                'users_id' => $cart->users_id,
                'products_id' => $cart->products_id
            ]);
        }

        //Delete Cart after Transaction

        Cart::where('users_id', Auth::user()->id)->delete();

        //Configuration Midtrans

        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        //Setup Variable Midtrans

        $midtrans = [
            'transaction_details' => [
                'order_id' => 'SKU-' . $transactions->id,
                'gross_amount' => (int) $transactions->total_price
            ],
            'customer_details' => [
                'first_name' => $transactions->name,
                'email'  => $transactions->email,
            ],
            'enabled_payment' => [
                'gopay', 'bank_transfer'
            ],
            'vt_web' => []
        ];

        //Payment Process

        try {
            // Get Snap Payment Page URL
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

            $transactions->payment_url = $paymentUrl;
            $transactions->save();

            // Redirect to Snap Payment Page
            return redirect($paymentUrl);
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function success(Request $request)
    {
        return view('pages.frontend.success');
    }

}
