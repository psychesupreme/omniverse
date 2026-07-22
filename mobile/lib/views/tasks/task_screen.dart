import 'dart:io' show Platform;
import 'package:flutter/material.dart';
import 'package:omniroute_mobile/collections/task_collection.dart';
import 'package:omniroute_mobile/repositories/task_repository.dart';
import 'package:omniroute_mobile/views/tasks/task_completion_sheet.dart';
import 'package:url_launcher/url_launcher.dart';

class TaskScreen extends StatefulWidget {
  final TaskRepository repository;

  const TaskScreen({super.key, required this.repository});

  @override
  State<TaskScreen> createState() => _TaskScreenState();
}

class _TaskScreenState extends State<TaskScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<TaskCollection> _allTasks = [];
  Map<String, dynamic>? _activeShift;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final tasks = await widget.repository.refreshTasks();
    final shift = await widget.repository.getActiveShift();

    if (mounted) {
      setState(() {
        _allTasks = tasks;
        _activeShift = shift;
        _isLoading = false;
      });
    }
  }

  Future<void> _handleRefresh() async {
    final tasks = await widget.repository.refreshTasks();
    final shift = await widget.repository.getActiveShift();

    if (mounted) {
      setState(() {
        _allTasks = tasks;
        _activeShift = shift;
      });
    }
  }

  Future<void> _launchNavigation(double lat, double lng) async {
    final String url = Platform.isIOS
        ? 'https://maps.apple.com/?daddr=$lat,$lng&dirflg=d'
        : 'https://www.google.com/maps/dir/?api=1&destination=$lat,$lng&travelmode=driving';

    final Uri uri = Uri.parse(url);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        await launchUrl(uri);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not launch navigation: $e')),
        );
      }
    }
  }

  Future<void> _startJob(TaskCollection task) async {
    final updated = await widget.repository.updateTaskStatus(
      taskId: task.id,
      status: 'in_progress',
    );
    if (updated != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Job started! Status updated to In Progress.')),
      );
      _handleRefresh();
    }
  }

  Future<void> _completeJob(TaskCollection task) async {
    final result = await TaskCompletionSheet.show(context);
    if (result != null) {
      final photoPaths = result.photos.map((e) => e.path).toList();
      final updated = await widget.repository.updateTaskStatus(
        taskId: task.id,
        status: 'completed',
        notes: result.notes,
        photos: photoPaths,
      );

      if (updated != null && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Task completed! Staged for background sync.')),
        );
        _handleRefresh();
      }
    }
  }

  List<TaskCollection> _filterTasks(String status) {
    return _allTasks.where((t) => t.status == status).toList();
  }

  Widget _buildActiveShiftBanner() {
    final isClockedIn = _activeShift != null && _activeShift!['active'] == true;
    final shiftData = _activeShift?['timesheet'];
    final siteName = shiftData?['geofence_name'] ?? 'Work Site';
    final clockInRaw = shiftData?['clock_in'];
    final clockInFormatted = clockInRaw != null
        ? DateTime.tryParse(clockInRaw.toString())?.toLocal().toString().substring(11, 16) ?? ''
        : '';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      decoration: BoxDecoration(
        color: isClockedIn ? Colors.green[50] : Colors.red[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isClockedIn ? Colors.green : Colors.red.shade200,
        ),
      ),
      child: Row(
        children: [
          Icon(
            isClockedIn ? Icons.circle : Icons.error_outline,
            color: isClockedIn ? Colors.green : Colors.red,
            size: 16,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              isClockedIn
                  ? '🟢 Clocked In at $siteName since $clockInFormatted'
                  : '🔴 Out of Site Boundary / Shift Inactive',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.bold,
                color: isClockedIn ? Colors.green[900] : Colors.red[900],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTaskList(String status) {
    final filtered = _filterTasks(status);

    if (filtered.isEmpty) {
      return RefreshIndicator(
        onRefresh: _handleRefresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            SizedBox(height: MediaQuery.of(context).size.height * 0.2),
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.assignment_outlined, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 12),
                  Text(
                    'No $status tasks found',
                    style: TextStyle(color: Colors.grey[600], fontSize: 16),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _handleRefresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: filtered.length,
        itemBuilder: (context, index) {
          final task = filtered[index];
          return _buildTaskCard(task);
        },
      ),
    );
  }

  Widget _buildTaskCard(TaskCollection task) {
    final bool hasLocation = task.latitude != null && task.longitude != null;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    task.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                if (!task.isSynced)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.orange[100],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.orange),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.sync_problem, size: 14, color: Colors.orange),
                        SizedBox(width: 4),
                        Text(
                          'Offline',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Colors.orange,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
            if (task.description != null && task.description!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                task.description!,
                style: TextStyle(color: Colors.grey[700], fontSize: 14),
              ),
            ],
            if (task.address != null && task.address!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.location_on, size: 16, color: Colors.grey),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      task.address!,
                      style: const TextStyle(color: Colors.grey, fontSize: 13),
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (hasLocation)
                  IconButton.filledTonal(
                    onPressed: () => _launchNavigation(task.latitude!, task.longitude!),
                    icon: const Icon(Icons.directions, color: Colors.blue),
                    tooltip: 'Start Turn-by-Turn Navigation',
                  )
                else
                  const SizedBox.shrink(),
                Row(
                  children: [
                    if (task.status == 'pending')
                      ElevatedButton.icon(
                        onPressed: () => _startJob(task),
                        icon: const Icon(Icons.play_arrow, size: 18),
                        label: const Text('Start Job'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                      ),
                    if (task.status == 'in_progress') ...[
                      ElevatedButton.icon(
                        onPressed: () => _completeJob(task),
                        icon: const Icon(Icons.check_circle_outline, size: 18),
                        label: const Text('Complete'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.green,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                      ),
                    ],
                    if (task.status == 'completed')
                      Chip(
                        avatar: const Icon(Icons.check, size: 16, color: Colors.green),
                        label: const Text('Completed'),
                        backgroundColor: Colors.green[50],
                      ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Assigned Tasks'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Pending'),
            Tab(text: 'In Progress'),
            Tab(text: 'Completed'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildActiveShiftBanner(),
                Expanded(
                  child: TabBarView(
                    controller: _tabController,
                    children: [
                      _buildTaskList('pending'),
                      _buildTaskList('in_progress'),
                      _buildTaskList('completed'),
                    ],
                  ),
                ),
              ],
            ),
    );
  }
}
