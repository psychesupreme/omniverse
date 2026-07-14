import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import 'package:path_provider/path_provider.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/tracking_log.dart';
import 'package:omniroute_mobile/services/api_service.dart';
import 'package:omniroute_mobile/services/location_service.dart';
import 'package:omniroute_mobile/repositories/sync_repository.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize background location tracking services
  await LocationService.initializeService();

  final dir = await getApplicationDocumentsDirectory();
  final isar = await Isar.open(
    [OutletSchema, TrackingLogSchema],
    directory: dir.path,
  );

  runApp(MyApp(isar: isar));
}

class MyApp extends StatelessWidget {
  final Isar isar;

  const MyApp({super.key, required this.isar});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'OmniRoute',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
        useMaterial3: true,
      ),
      home: MyHomePage(isar: isar),
    );
  }
}

class MyHomePage extends StatefulWidget {
  final Isar isar;

  const MyHomePage({super.key, required this.isar});

  @override
  State<MyHomePage> createState() => _MyHomePageState();
}

class _MyHomePageState extends State<MyHomePage> {
  late final ApiService _apiService;
  late final SyncRepository _syncRepository;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _apiService = ApiService();
    _syncRepository = SyncRepository(widget.isar);
  }

  /// Triggers a test pull sync and stores the payload in the local database
  Future<void> _performSync() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // Pull data using actual seeded Sanctum token
      final data = await _apiService.pullSync('acme', '1|yfjt3ozySHpLlvFLsNsqmLKvFErORfJX3HGUovna80a03f55', '');
      
      // Save data locally using sync repository
      if (data.containsKey('data') && data['data'] != null) {
        await _syncRepository.savePulledData(data['data'] as Map<String, dynamic>);
      } else {
        await _syncRepository.savePulledData(data);
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Sync completed successfully!')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Sync failed: $e')),
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
      appBar: AppBar(
        title: const Text('OmniRoute'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: Center(
        child: _isLoading
            ? const Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('Synchronizing data...'),
                ],
              )
            : const Text(
                'Database Initialized',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _isLoading ? null : _performSync,
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
        child: const Icon(Icons.sync),
      ),
    );
  }
}
