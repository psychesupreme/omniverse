import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import 'package:uuid/uuid.dart';
import 'package:omniroute_mobile/collections/order_collection.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/product_collection.dart';

class OrderCartItem {
  final ProductCollection product;
  int quantity;

  OrderCartItem({required this.product, required this.quantity});

  double get subtotal => product.unitPrice * quantity;
}

class CreateOrderScreen extends StatefulWidget {
  final Isar isar;
  final List<OrderCartItem> cartItems;
  final VoidCallback onOrderPlaced;

  const CreateOrderScreen({
    super.key,
    required this.isar,
    required this.cartItems,
    required this.onOrderPlaced,
  });

  @override
  State<CreateOrderScreen> createState() => _CreateOrderScreenState();
}

class _CreateOrderScreenState extends State<CreateOrderScreen> {
  final TextEditingController _notesController = TextEditingController();
  List<Outlet> _outlets = [];
  Outlet? _selectedOutlet;
  bool _isLoadingOutlets = true;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadOutlets();
  }

  Future<void> _loadOutlets() async {
    final outlets = await widget.isar.outlets.where().findAll();
    if (mounted) {
      setState(() {
        _outlets = outlets;
        if (_outlets.isNotEmpty) {
          _selectedOutlet = _outlets.first;
        }
        _isLoadingOutlets = false;
      });
    }
  }

  double get _totalAmount {
    return widget.cartItems.fold(0.0, (sum, item) => sum + item.subtotal);
  }

  Future<void> _placeOrder() async {
    if (widget.cartItems.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cart is empty. Select products first.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final String orderUuid = const Uuid().v4();
      final String orderNum = 'ORD-${DateTime.now().millisecondsSinceEpoch}';

      final List<Map<String, dynamic>> itemsPayload = widget.cartItems.map((item) {
        return {
          'product_id': item.product.id,
          'quantity': item.quantity,
          'unit_price': item.product.unitPrice,
          'subtotal': item.subtotal,
        };
      }).toList();

      final order = OrderCollection()
        ..id = orderUuid
        ..orderNumber = orderNum
        ..outletId = _selectedOutlet?.fastId
        ..totalAmount = _totalAmount
        ..status = 'pending'
        ..notes = _notesController.text.trim()
        ..itemsJson = jsonEncode(itemsPayload)
        ..isSynced = false
        ..placedAt = DateTime.now();

      await widget.isar.writeTxn(() async {
        await widget.isar.orderCollections.put(order);
      });

      widget.onOrderPlaced();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Order #$orderNum placed! Staged offline.')),
        );
        Navigator.of(context).pop();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to place order: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Review & Place Order'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Select Customer Outlet',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            _isLoadingOutlets
                ? const CircularProgressIndicator()
                : Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey[300]!),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<Outlet>(
                        isExpanded: true,
                        value: _selectedOutlet,
                        hint: const Text('Select Outlet'),
                        items: _outlets.map((outlet) {
                          return DropdownMenuItem<Outlet>(
                            value: outlet,
                            child: Text(outlet.name),
                          );
                        }).toList(),
                        onChanged: (val) {
                          setState(() => _selectedOutlet = val);
                        },
                      ),
                    ),
                  ),
            const SizedBox(height: 20),
            Text(
              'Order Items Summary',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: widget.cartItems.length,
              itemBuilder: (context, index) {
                final item = widget.cartItems[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(item.product.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('${item.quantity} x \$${item.product.unitPrice.toStringAsFixed(2)}'),
                    trailing: Text(
                      '\$${item.subtotal.toStringAsFixed(2)}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                  ),
                );
              },
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _notesController,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: 'Delivery Notes / Special Instructions',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.indigo[50],
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.indigo[100]!),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total Amount Payable:', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
                  Text(
                    '\$${_totalAmount.toStringAsFixed(2)}',
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.indigo),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _placeOrder,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.indigo,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: _isSubmitting
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text(
                        'Confirm & Place Order',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
