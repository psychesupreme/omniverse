import 'dart:async';
import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import '../services/api_service.dart';
import '../services/location_service.dart';
import '../services/session_service.dart';
import '../repositories/sync_repository.dart';
import '../collections/tracking_log.dart';
import 'login_screen.dart';

class DashboardScreen extends StatefulWidget {
  final Isar isar;

  const DashboardScreen({super.key, required this.isar});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late final ApiService _apiService;
  late final SyncRepository _syncRepository;
  bool _isLoading = false;
  bool _isShiftActive = false;
  int _localLogCount = 0;
  Timer? _logTimer;

  String _workerName = '';
  String _workerEmail = '';
  String _tenantId = '';
  String _token = '';
  int _userId = 0;

  @override
  void initState() {
    super.initState();
    _apiService = ApiService();
    _syncRepository = SyncRepository(widget.isar);
    _loadSessionAndStatus();
    
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

  /// Loads session credentials and verifies shift background tracking status
  Future<void> _loadSessionAndStatus() async {
    final session = await SessionService.getSession();
    if (session != null) {
      setState(() {
        _workerName = session['name'] as String;
        _workerEmail = session['email'] as String;
        _tenantId = session['tenantId'] as String;
        _token = session['token'] as String;
        _userId = session['userId'] as int;
      });
    }
    await _checkShiftStatus();
    await _updateLogCount();
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
        // Start shift for currently logged-in user ID
        await LocationService.startShift(_userId);
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
    if (_token.isEmpty || _tenantId.isEmpty) return;

    setState(() {
      _isLoading = true;
    });

    try {
      // 1. Push any unsynced tracking logs cached locally
      final pushedCount = await _syncRepository.pushUnsyncedLogs(
        _apiService,
        _tenantId,
        _token,
      );

      // 2. Pull data using actual seeded Sanctum token
      final data = await _apiService.pullSync(_tenantId, _token, '');
      
      // Save data locally using sync repository
      if (data.containsKey('data') && data['data'] != null) {
        await _syncRepository.savePulledData(data['data'] as Map<String, dynamic>);
      } else {
        await _syncRepository.savePulledData(data);
      }

      await _updateLogCount();

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

  /// Revokes active tracking, clears local storage session, and redirects to login
  Future<void> _handleLogout() async {
    setState(() {
      _isLoading = true;
    });

    try {
      if (_isShiftActive) {
        await LocationService.stopShift();
      }
      await SessionService.clearSession();
      if (mounted) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => LoginScreen(isar: widget.isar)),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Logout failed: $e')),
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
        title: const Text('OmniRoute Tracker'),
        backgroundColor: Colors.teal[800],
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Logout',
            onPressed: _isLoading ? null : _handleLogout,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('Processing...'),
                ],
              ),
            )
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // User Profile banner card
                  Card(
                    elevation: 3,
                    shadowColor: Colors.teal.withOpacity(0.1),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    color: Colors.white,
                    child: Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 30,
                            backgroundColor: Colors.teal[50],
                            child: Text(
                              _workerName.isNotEmpty ? _workerName[0].toUpperCase() : 'U',
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.teal[800],
                              ),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _workerName,
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.teal[900],
                                  ),
                                ),
                                Text(
                                  _workerEmail,
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: Colors.grey[600],
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.teal[50],
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Text(
                                    'Tenant: ${_tenantId.toUpperCase()}',
                                    style: TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.teal[800],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Tracking status card
                  Card(
                    elevation: 2,
                    shadowColor: Colors.teal.withOpacity(0.05),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    color: Colors.white,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 24.0, horizontal: 16.0),
                      child: Column(
                        children: [
                          Icon(
                            _isShiftActive ? Icons.radar_rounded : Icons.radar_outlined,
                            size: 64,
                            color: _isShiftActive ? Colors.green : Colors.grey,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            _isShiftActive ? 'ACTIVE SHIFT' : 'SHIFT INACTIVE',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w900,
                              color: _isShiftActive ? Colors.green[700] : Colors.grey[600],
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _isShiftActive
                                ? 'GPS coordinates are logged in the background every 60s'
                                : 'Start your shift to begin tracking location',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey[600],
                            ),
                          ),
                          const SizedBox(height: 24),
                          ElevatedButton(
                            onPressed: _toggleShift,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: _isShiftActive ? Colors.red[600] : Colors.green[600],
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 48, vertical: 16),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            child: Text(_isShiftActive ? 'END SHIFT' : 'START SHIFT'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Unsynced Logs metric card
                  Card(
                    elevation: 2,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    color: Colors.teal[50],
                    child: Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Column(
                        children: [
                          Text(
                            'Unsynced Location Logs',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: Colors.teal[800],
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '$_localLogCount',
                            style: TextStyle(
                              fontSize: 48,
                              fontWeight: FontWeight.w900,
                              color: Colors.teal[900],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
      floatingActionButton: FloatingActionButton(
        onPressed: _isLoading ? null : _performSync,
        backgroundColor: Colors.teal[800],
        foregroundColor: Colors.white,
        tooltip: 'Sync Data',
        child: const Icon(Icons.sync),
      ),
    );
  }
}
