<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('totalsells')->paginate(8);
        return view('user.index',compact('products'));
    }

    public function products()
    {
        $products = Product::all();
        return view('user.products',compact('products'));
    }

    public function productdetail($id)
    {
        $productid = $id;
        $product = Product::find($id);
        $feedbacks = Feedback::where('product_id', '=', $id)->latest()->get();
        $relatedproducts = Product::where('category_id', '=', $product->category_id)->where('id', '!=', $product->id)->orderBy('totalsells', 'desc')->paginate(4);

        return view('user.productdetail',compact('product','relatedproducts','feedbacks','productid'));
    }


    public function updatepan(Request $request, $id)
    {
        $user = User::find($id);
        $data = $request->validate([
            'phone' => 'required',
            'address' => 'required',
            'panimage' => 'required',
            'pannumber' => 'required',
        ]);

        if($request->hasFile('panimage')){
            $panimage = $request->file('panimage');
            $name = time().'.'.$panimage->getClientOriginalExtension();
            $destinationPath = public_path('/images/pan');
            $panimage->move($destinationPath,$name);
            $data['panimage'] = $name;
        }

        $user->update($data);
        return redirect(route('user.buyersell'))->with('success','User Updated Successfully');
    }

    

    public function checkout()
    {
        if(!auth()->user())
        {
            $itemsincart = 0;
        }
        else
        {
            $itemsincart = Cart::where('user_id',auth()->user()->id)->count();
        }
        $carts = Cart::where('user_id',auth()->user()->id)->get();
        return view('user.checkout',compact('carts','itemsincart'));
    }
}
