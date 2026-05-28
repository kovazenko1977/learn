<?php
namespace App\Controllers;
use App\Models\Product;
use App\Models\Category;
class ProductController extends BaseController {
    private Product $productModel;
    public function __construct() {
        $this->productModel = new Product();
    }
    public function index() {
        $products = $this->productModel->getAll();
        $this->json($products);
    }
    public function show($id) {
        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
        }
        $this->json($product);
    }
    public function store() {
        $data = $this->getRequestData();
        if (empty($data['name'])) {
            $this->json(['error' => 'Name is required'], 400);
        }
        $id = $this->productModel->create($data);
        $this->json(['id' => $id, 'message' => 'Product created']);
    }
    public function update($id) {
        $data = $this->getRequestData();
        $this->productModel->update($id, $data);
        $this->json(['message' => 'Product updated']);
    }
    public function destroy($id) {
        $this->productModel->delete($id);
        $this->json(['message' => 'Product deleted']);
    }
    public function categories() {
        $categoryModel = new Category();
        $this->json($categoryModel->getAll());
    }
}
