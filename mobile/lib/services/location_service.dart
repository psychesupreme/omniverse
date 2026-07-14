import 'dart:async';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_background_service/flutter_background_service.dart';
import 'package:geolocator/geolocator.dart';
import 'package:isar/isar.dart';
import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/tracking_log.dart';

class LocationService {
  static const String notificationChannelId = 'location_tracking';
  static const int notificationId = 888;

  /// Initialize the background service configuration parameters
  static Future<void> initializeService() async {
    final service = FlutterBackgroundService();

    await service.configure(
      androidConfiguration: AndroidConfiguration(
        onStart: onStart,
        autoStart: false,
        isForegroundMode: true,
        notificationChannelId: notificationChannelId,
        initialNotificationTitle: 'Shift Active',
        initialNotificationContent: 'Tracking location...',
        foregroundServiceNotificationId: notificationId,
      ),
      iosConfiguration: IosConfiguration(
        autoStart: false,
        onForeground: onStart,
        onBackground: onIosBackground,
      ),
    );
  }

  @pragma('vm:entry-point')
  static Future<bool> onIosBackground(ServiceInstance service) async {
    return true;
  }

  @pragma('vm:entry-point')
  static void onStart(ServiceInstance service) async {
    DartPluginRegistrant.ensureInitialized();
    WidgetsFlutterBinding.ensureInitialized();

    // Initialize dedicated Isar instance inside the background isolate context
    final dir = await getApplicationDocumentsDirectory();
    final isar = await Isar.open(
      [OutletSchema, TrackingLogSchema],
      directory: dir.path,
    );

    if (service is AndroidServiceInstance) {
      service.on('setAsForeground').listen((event) {
        service.setAsForegroundService();
      });

      service.on('setAsBackground').listen((event) {
        service.setAsBackgroundService();
      });
    }

    service.on('stopService').listen((event) async {
      await isar.close();
      service.stopSelf();
    });

    int currentUserId = 1; // Default fallback user ID
    service.on('setUserId').listen((event) {
      if (event != null && event['userId'] != null) {
        currentUserId = event['userId'] as int;
      }
    });

    // Run location polling every 60 seconds
    Timer.periodic(const Duration(seconds: 60), (timer) async {
      if (service is AndroidServiceInstance) {
        if (await service.isForegroundService()) {
          service.setForegroundNotificationInfo(
            title: "Shift Active",
            content: "Tracking location logs...",
          );
        }
      }

      try {
        final position = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.high,
            timeLimit: Duration(seconds: 10),
          ),
        );

        final String logUuid = const Uuid().v4();
        final DateTime utcNow = DateTime.now().toUtc();

        final trackingLog = TrackingLog()
          ..fastId = logUuid
          ..userId = currentUserId
          ..latitude = position.latitude
          ..longitude = position.longitude
          ..version = 1
          ..lastUpdatedAt = utcNow
          ..isSynced = false
          ..isMocked = position.isMocked; // Anti-spoofing check

        // Write directly to local Isar database
        await isar.writeTxn(() async {
          await isar.trackingLogs.put(trackingLog);
        });

        // Broadcast local update events
        service.invoke(
          'onLocationUpdated',
          {
            "latitude": position.latitude,
            "longitude": position.longitude,
            "isMocked": position.isMocked,
            "timestamp": utcNow.toIso8601String(),
          },
        );
      } catch (e) {
        service.invoke('onError', {"message": e.toString()});
      }
    });
  }

  /// Start background tracking service for the current user
  static Future<void> startShift(int userId) async {
    final service = FlutterBackgroundService();

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.deniedForever || permission == LocationPermission.denied) {
        throw Exception("GPS Location permission is required to start shift.");
      }
    }

    final isStarted = await service.startService();
    if (isStarted) {
      Future.delayed(const Duration(milliseconds: 500), () {
        service.invoke('setUserId', {'userId': userId});
      });
    }
  }

  /// Stop tracking and shut down background service isolate
  static Future<void> stopShift() async {
    final service = FlutterBackgroundService();
    service.invoke('stopService');
  }

  /// Check active service status
  static Future<bool> isShiftActive() async {
    final service = FlutterBackgroundService();
    return await service.isRunning();
  }
}
