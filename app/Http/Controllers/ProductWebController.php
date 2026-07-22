<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductWebController extends Controller
{
    public function index(): Response
    {
        $products = Product::latest()->paginate(15);
        return Inertia::render('Dispatch/Products/Index', [
            'products' => $products,
        ]);
    }
}
