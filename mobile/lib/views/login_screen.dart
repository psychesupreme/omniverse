import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import '../services/api_service.dart';
import '../services/session_service.dart';
import 'dashboard_screen.dart';

class LoginScreen extends StatefulWidget {
  final Isar isar;

  const LoginScreen({super.key, required this.isar});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tenantController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiService = ApiService();
  bool _isLoading = false;

  @override
  void dispose() {
    _tenantController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
    });

    final tenant = _tenantController.text.trim();
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    try {
      final response = await _apiService.login(tenant, email, password);
      if (response != null && mounted) {
        final token = response['token'] as String;
        final userId = response['user_id'] as int;
        final name = response['name'] as String;

        await SessionService.saveSession(
          token: token,
          tenantId: tenant,
          userId: userId,
          name: name,
          email: email,
        );

        if (mounted) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (context) => DashboardScreen(isar: widget.isar)),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceAll('Exception: ', '')),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Premium Logo/Header
              Icon(
                Icons.alt_route_rounded,
                size: 72,
                color: Colors.teal[800],
              ),
              const SizedBox(height: 16),
              Text(
                'OmniRoute',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  color: Colors.teal[900],
                  letterSpacing: 1.2,
                ),
              ),
              Text(
                'Field Force Tracking Portal',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.teal[600],
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 48),

              // Login Input Form Card
              Card(
                elevation: 4,
                shadowColor: Colors.teal.withOpacity(0.1),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                color: Colors.white,
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          'Sign In',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.teal[900],
                          ),
                        ),
                        const SizedBox(height: 24),

                        // Tenant Field
                        TextFormField(
                          controller: _tenantController,
                          enabled: !_isLoading,
                          decoration: InputDecoration(
                            labelText: 'Tenant ID',
                            hintText: 'e.g. acme',
                            prefixIcon: const Icon(Icons.domain),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            labelStyle: TextStyle(color: Colors.teal[700]),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(color: Colors.teal[800]!, width: 2),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Please enter your tenant ID';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Email Field
                        TextFormField(
                          controller: _emailController,
                          enabled: !_isLoading,
                          keyboardType: TextInputType.emailAddress,
                          decoration: InputDecoration(
                            labelText: 'Email Address',
                            prefixIcon: const Icon(Icons.email_outlined),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            labelStyle: TextStyle(color: Colors.teal[700]),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(color: Colors.teal[800]!, width: 2),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Please enter your email';
                            }
                            if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                              return 'Please enter a valid email address';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Password Field
                        TextFormField(
                          controller: _passwordController,
                          enabled: !_isLoading,
                          obscureText: true,
                          decoration: InputDecoration(
                            labelText: 'Password',
                            prefixIcon: const Icon(Icons.lock_outline),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            labelStyle: TextStyle(color: Colors.teal[700]),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(color: Colors.teal[800]!, width: 2),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your password';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),

                        // Submit Button
                        ElevatedButton(
                          onPressed: _isLoading ? null : _handleLogin,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.teal[800],
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 2,
                          ),
                          child: _isLoading
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2.5,
                                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                  ),
                                )
                              : const Text(
                                  'LOGIN',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
