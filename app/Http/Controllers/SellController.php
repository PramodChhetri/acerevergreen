<?php

namespace App\Http\Controllers;

use Intervention\Image\ImageManagerStatic as Image;
use App\Models\approval;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellController extends Controller
{
    public function index()
    {       
        $userid = Auth::user()->id;
        return view('user.sell.index',compact('userid'));
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
        approval::create([
            'user_id' => $user->id, 
        ]);

        return redirect(route('user.sell.index'))->with('success','User Updated Successfully');
    }

    public function manageproducts()
    {       
        $products = Product::where('user_id', '=', Auth::user()->id)->get();
        return view('user.sell.manageproducts',compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('user.sell.create',compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required',
            'name' => 'required',
            'price' => 'numeric|required',
            'stock' => 'numeric|required',
            'description' => 'required',
            'condition' => 'required',
            'brand' => 'required',
            'photopath' => 'required'
        ]);

        if($request->hasFile('photopath')){
            $image = $request->file('photopath');
            $name = time().'.'.$image->getClientOriginalExtension();
            // Image::make($image)->resize(300, 300)->save('images/products/'.$name);
            $destinationPath = public_path('/images/products');
            $image->move($destinationPath,$name);
            $data['photopath'] = $name;
        }

        Product::create(array_merge($data, ['user_id' => Auth::user()->id]));
        return redirect(route('user.sell.manageproducts'))->with('success','Product Created Successfully');
    }

    public function edit($id)
    {
        $product = Product::find($id);
        $categories = Category::all();
        return view('user.sell.edit',compact('product','categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        $data = $request->validate([
            'category_id' => 'required',
            'name' => 'required|unique:products,name,'.$product->id,
            'price' => 'numeric|required',
            'stock' => 'numeric|required',
            'description' => 'required',
            'condition' => 'required',
            'brand' => 'required',
            'photopath' => 'nullable'
        ]);

        if($request->hasFile('photopath')){
            $image = $request->file('photopath');
            $name = time().'.'.$image->getClientOriginalExtension();
            // Image::make($image)->resize(300, 300)->save('images/products/'.$name);
            $destinationPath = public_path('/images/products');
            $image->move($destinationPath,$name);
            unlink('images/products/'.$product->photopath);
            // File::delete(public_path('images/products/'.$product->photopath));
            $data['photopath'] = $name;
        }

        $product->update($data);
        return redirect(route('user.sell.manageproducts'))->with('success','Product Updated Successfully');
    }

    public function destroy(Request $request)
    {
        $product = Product::find($request->dataid);
        unlink('images/products/'.$product->photopath);
        $product->delete();
        return redirect(route('user.sell.manageproducts'))->with('success','Product Deleted Successfully!');
    }

    public function managestocks()
    {
        $products = Product::where('user_id', '=', Auth::user()->id)->get();
        return view('user.sell.managestocks', compact('products'));
    }
    

    public function stockupdate(Request $request, $id)
    {
        $product = Product::find($id);
        $data = $request->validate([
            'stock' => 'numeric|required',
        ]);

        $product->update($data);
        return back()->with('success','Stock Updated Successfully');
    }

    public function orders()
    {
        // Retrieve the authenticated buyer's ID
    $sellerId = auth()->user()->id;

    // Retrieve orders for the specific buyer
    $orders = Order::with('buyer')
        ->where('seller_id', $sellerId)
        ->where('status','Pending')
        ->orderBy('status','desc')->get();

    // Pass the list of orders to the seller's dashboard view
    return view('user.sell.orders', compact('orders'));
    }

    public function ordersapproved()
    {
        // Retrieve the authenticated buyer's ID
    $sellerId = auth()->user()->id;

    // Retrieve orders for the specific buyer
    $orders = Order::with('buyer')
        ->where('seller_id', $sellerId)
        ->where('status','Approved')
        ->orderBy('status','desc')->get();

    // Pass the list of orders to the seller's dashboard view
    return view('user.sell.ordersapproved', compact('orders'));
    }

    public function orderscompleted()
    {
        // Retrieve the authenticated buyer's ID
    $sellerId = auth()->user()->id;

    // Retrieve orders for the specific buyer
    $orders = Order::with('buyer')
        ->where('seller_id', $sellerId)
        ->where('status','Completed')
        ->orderBy('status','desc')->get();

    // Pass the list of orders to the seller's dashboard view
    return view('user.sell.orderscompleted', compact('orders'));
    }

    public function ordersreturned()
    {
        // Retrieve the authenticated buyer's ID
    $sellerId = auth()->user()->id;

    // Retrieve orders for the specific buyer
    $orders = Order::with('buyer')
        ->where('seller_id', $sellerId)
        ->where('status','Returned')
        ->orderBy('status','desc')->get();

    // Pass the list of orders to the seller's dashboard view
    return view('user.sell.ordersreturned', compact('orders'));
    }

    public function ordersapprove($id)
    {
        $order = Order::find($id);
        $quantity = $order->quantity;
        $productId = $order->product_id;
        $product = Product::find($productId);
        $stock = $product->stock;
        
        if($stock < $quantity){
        return redirect(route('user.sell.orders'))->with('success','Out of Stock!');
        }else{
            $product->update([
                'stock' => $stock - $quantity
            ]);
            $order->update([
                'status' => 'Approved'
            ]);
        return redirect(route('user.sell.orders'))->with('success','Order Approved Successfully!');
        }
    }

    public function orderscomplete($id)
    {
        $order = Order::find($id);
        $product = Product::find($order->product_id);
        $quantity = $order->quantity;
        $productsales = $product->totalsells;

        $order->update([
            'status' => 'Completed'
            ]);

        $product->update([
            'totalsells' => $productsales + $quantity
        ]);
        
        return redirect(route('user.sell.ordersapproved'))->with('success','Order Complete Successfully!');
    }

    public function ordersreturn($id)
    {
        $order = Order::find($id);
        $product = Product::find($order->product_id);
        $quantity = $order->quantity;
        $productsales = $product->totalsells;

        $order->update([
            'status' => 'Returned'
            ]);

        if($productsales >= $quantity){
            $product->update([
                'totalsells' => $productsales - $quantity
            ]);
        }
        

        return back()->with('success','Order Returnd');
    }

    public function ordersdestroy(Request $request)
    {
        $orders= Order::find($request->dataid);
        $orders->delete();
        return redirect(route('user.sell.orders'))->with('success','Order Deleted Successfully!');
    }
}
