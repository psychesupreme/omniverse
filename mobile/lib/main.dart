import 'dart:async';
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
  bool _isShiftActive = false;
  int _localLogCount = 0;
  Timer? _logTimer;

  @override
  void initState() {
    super.initState();
    _apiService = ApiService();
    _syncRepository = SyncRepository(widget.isar);
    _checkShiftStatus();
    _updateLogCount();
    
    // Periodically update the log count to show incoming coordinates in real-time
    _logTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      _updateLogCount();
    });
  }

  @override
  void dispose() {
    _logTimer?.cancel();
    super.dispose();
  }

  /// Queries Isar for the count of locally stored tracking logs
  Future<void> _updateLogCount() async {
    final count = await widget.isar.trackingLogs.filter().isSyncedEqualTo(false).count();
    if (mounted) {
      setState(() {
        _localLogCount = count;
      });
    }
  }

  /// Initial checks to see if the background service is running
  Future<void> _checkShiftStatus() async {
    final active = await LocationService.isShiftActive();
    setState(() {
      _isShiftActive = active;
    });
  }

  /// Toggles the background geolocation shift tracking states
  Future<void> _toggleShift() async {
    setState(() {
      _isLoading = true;
    });

    try {
      if (_isShiftActive) {
        await LocationService.stopShift();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Shift ended. Tracking disabled.')),
          );
        }
      } else {
        // Start shift for user ID 1 (Dave)
        await LocationService.startShift(1);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Shift started. Tracking active.')),
          );
        }
      }
      // Give a tiny delay for service isolate bootstrapping
      await Future.delayed(const Duration(milliseconds: 500));
      await _checkShiftStatus();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to toggle shift: $e')),
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

  /// Triggers a test push/pull sync and stores the payload in the local database
  Future<void> _performSync() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // 1. Push any unsynced tracking logs cached locally
      final pushedCount = await _syncRepository.pushUnsyncedLogs(
        _apiService,
        'acme',
        '1|yfjt3ozySHpLlvFLsNsqmLKvFErORfJX3HGUovna80a03f55',
      );

      // 2. Pull data using actual seeded Sanctum token
      final data = await _apiService.pullSync('acme', '1|yfjt3ozySHpLlvFLsNsqmLKvFErORfJX3HGUovna80a03f55', '');
      
      // Save data locally using sync repository
      if (data.containsKey('data') && data['data'] != null) {
        await _syncRepository.savePulledData(data['data'] as Map<String, dynamic>);
      } else {
        await _syncRepository.savePulledData(data);
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Sync complete! Pushed $pushedCount logs.')),
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
                  Text('Processing request...'),
                ],
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text(
                    'Database Initialized',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 32),
                  ElevatedButton(
                    onPressed: _toggleShift,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _isShiftActive ? Colors.red : Colors.green,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 48, vertical: 20),
                      textStyle: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    child: Text(_isShiftActive ? 'END SHIFT' : 'START SHIFT'),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _isShiftActive
                        ? 'Tracker: ACTIVE - Logging every 60s'
                        : 'Tracker: Inactive',
                    style: TextStyle(
                      fontSize: 14,
                      color: _isShiftActive ? Colors.green : Colors.grey,
                      fontWeight: _isShiftActive ? FontWeight.bold : FontWeight.normal,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Stored Location Logs: $_localLogCount',
                    style: const TextStyle(
                      fontSize: 14,
                      color: Colors.teal,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
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
