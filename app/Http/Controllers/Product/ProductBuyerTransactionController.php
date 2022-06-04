<?php

namespace App\Http\Controllers\Product;

use App\User;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Transaction;

class ProductBuyerTransactionController extends ApiController
{

    public function store(Request $request, Product $product, User $buyer)
    {
        $rules = [
            'quantity' => 'required|integer|min:1',
        ];

        $this->validate($request,$rules);

        if($buyer->id == $product->seller_id){
            return $this->errorResponse('El comprador debe ser diferente al vendedor',404);
        }

        if(!$buyer->esVerificado()){
            return $this->errorResponse('El comprador debe ser un usuario verificado',409);
        }

        if(!$product->seller->esVerificado()){
            return $this->errorResponse('El vendedor debe ser un usuario verificado',409);
        }

        if(!$product->estaDisponible()){
            return $this->errorResponse('El producto para está transacción no está disponible',409);
        }

        if($product->quantity < $request->quantity){
            return $this->errorResponse('El producto no tiene la cantidad disponible requerida para está transacción',409);
        }

        return DB::transaction(function () use ($request, $product, $buyer) {
            $product->quantity -= $request->quantity;
            $product->save();

            $transaction = Transaction::create([
                'quantity' => $request->quantity,
                'buyer_id' => $buyer->id,
                'product_id' => $product->id
            ]);

            return $this->showOne($transaction,201);
        });
    }


}
