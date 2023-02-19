<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ResponseTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();
        try {

            if ($request->has('product_name')) {
                $query->where('name', 'like', '%' . $request->input('name') . '%');
            }

            if ($request->has('person_name')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->input('person') . '%');
                });
            }

            $products = $query->orderBy('created_at', 'desc')->paginate(10);
            $this->data = $products;
        } catch (Exception $exception) {
            $this->message = $exception->getMessage();
        }
        return $this->apiResponse();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        return $this->apiResponse();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $this->errors = $validator->errors();
            $this->status = 422;
            return $this->apiResponse();
        }

        try {

            $userId = User::find(1 || $request->user_id); // TODO: change this to use the actual user ID who added the product i.e auth->id()

            DB::beginTransaction();

            $product = new Product();
            $product->fill($request->only(['name', 'price', 'status', 'type']));
            $product->user_id = $userId->id;
            $product->save();

            $product->addHistory($product->user_id, $product->id, $product->status);

            DB::commit();

            $this->data = $product;
        } catch (Exception $exception) {
            $this->message = $exception->getMessage();
        }
        return $this->apiResponse();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id = null): JsonResponse
    {
        try {
            if ($id != null) {
                $product = Product::findOrFail($id);
            } else {
                $product = Product::all();
            }
            $this->data = $product;
        } catch (Exception $exception) {
            $this->message = $exception->getMessage();
        }
        return $this->apiResponse();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): JsonResponse
    {
        return $this->apiResponse();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    Rule::unique('products')->ignore($product->id),
                ],
                'price' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $this->errors = $validator->errors();
                $this->status = 422;
                return $this->apiResponse();
            }
            $product->fill($request->only(['name', 'price', 'status', 'type']));
            $product->save();

            $this->data = $product;
        } catch (Exception $exception) {
            $this->message = $exception->getMessage();
        }
        return $this->apiResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {

            $product = Product::findOrFail($id);
            $product->delete();
            $this->message = "Product with id:$id deleted successfully.";
        } catch (Exception $exception) {
            $this->message = $exception->getMessage();
        }
        return $this->apiResponse();
    }
}
