import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import 'package:omniroute_mobile/collections/product_collection.dart';
import 'package:omniroute_mobile/views/sales/create_order_screen.dart';

class ProductCatalogScreen extends StatefulWidget {
  final Isar isar;

  const ProductCatalogScreen({super.key, required this.isar});

  @override
  State<ProductCatalogScreen> createState() => _ProductCatalogScreenState();
}

class _ProductCatalogScreenState extends State<ProductCatalogScreen> {
  final TextEditingController _searchController = TextEditingController();
  List<ProductCollection> _allProducts = [];
  List<ProductCollection> _filteredProducts = [];
  final Map<String, int> _cartQuantities = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  Future<void> _loadProducts() async {
    final products = await widget.isar.productCollections.where().findAll();
    if (mounted) {
      setState(() {
        _allProducts = products;
        _filteredProducts = products;
        _isLoading = false;
      });
    }
  }

  void _filterProducts(String query) {
    if (query.trim().isEmpty) {
      setState(() => _filteredProducts = _allProducts);
      return;
    }

    final lowerQuery = query.toLowerCase();
    setState(() {
      _filteredProducts = _allProducts.where((p) {
        return p.name.toLowerCase().contains(lowerQuery) ||
            p.sku.toLowerCase().contains(lowerQuery);
      }).toList();
    });
  }

  void _updateQuantity(String productId, int delta) {
    setState(() {
      final current = _cartQuantities[productId] ?? 0;
      final updated = current + delta;
      if (updated <= 0) {
        _cartQuantities.remove(productId);
      } else {
        _cartQuantities[productId] = updated;
      }
    });
  }

  int get _totalCartItemCount {
    return _cartQuantities.values.fold(0, (sum, q) => sum + q);
  }

  double get _totalCartPrice {
    double total = 0.0;
    _cartQuantities.forEach((productId, qty) {
      final p = _allProducts.firstWhere((prod) => prod.id == productId);
      total += p.unitPrice * qty;
    });
    return total;
  }

  void _clearCart() {
    setState(() {
      _cartQuantities.clear();
    });
  }

  void _navigateToCreateOrder() {
    final List<OrderCartItem> cartItems = [];
    _cartQuantities.forEach((productId, qty) {
      final p = _allProducts.firstWhere((prod) => prod.id == productId);
      cartItems.add(OrderCartItem(product: p, quantity: qty));
    });

    if (cartItems.isEmpty) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => CreateOrderScreen(
          isar: widget.isar,
          cartItems: cartItems,
          onOrderPlaced: _clearCart,
        ),
      ),
    );
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Product Catalogue'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              onChanged: _filterProducts,
              decoration: InputDecoration(
                hintText: 'Search by SKU or product name...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredProducts.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey[400]),
                            const SizedBox(height: 12),
                            Text(
                              'No products found in local catalogue',
                              style: TextStyle(color: Colors.grey[600], fontSize: 16),
                            ),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _filteredProducts.length,
                        itemBuilder: (context, index) {
                          final product = _filteredProducts[index];
                          final qty = _cartQuantities[product.id] ?? 0;

                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            elevation: 2,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(12),
                                    decoration: BoxDecoration(
                                      color: Colors.indigo[50],
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Icon(Icons.shopping_bag, color: Colors.indigo[600]),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          product.name,
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                        ),
                                        Text(
                                          'SKU: ${product.sku}',
                                          style: const TextStyle(color: Colors.grey, fontSize: 12),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '\$${product.unitPrice.toStringAsFixed(2)}',
                                          style: const TextStyle(
                                            color: Colors.indigo,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 15,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Row(
                                    children: [
                                      if (qty > 0) ...[
                                        IconButton(
                                          onPressed: () => _updateQuantity(product.id, -1),
                                          icon: const Icon(Icons.remove_circle_outline, color: Colors.red),
                                        ),
                                        Text(
                                          '$qty',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                        ),
                                      ],
                                      IconButton(
                                        onPressed: () => _updateQuantity(product.id, 1),
                                        icon: const Icon(Icons.add_circle_outline, color: Colors.green),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
      floatingActionButton: _totalCartItemCount > 0
          ? FloatingActionButton.extended(
              onPressed: _navigateToCreateOrder,
              backgroundColor: Colors.indigo,
              icon: const Icon(Icons.shopping_cart, color: Colors.white),
              label: Text(
                'Checkout ($_totalCartItemCount) - \$${_totalCartPrice.toStringAsFixed(2)}',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
              ),
            )
          : null,
    );
  }
}
