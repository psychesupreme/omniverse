// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tracking_log.dart';

// **************************************************************************
// IsarCollectionGenerator
// **************************************************************************

// coverage:ignore-file
// ignore_for_file: duplicate_ignore, non_constant_identifier_names, constant_identifier_names, invalid_use_of_protected_member, unnecessary_cast, prefer_const_constructors, lines_longer_than_80_chars, require_trailing_commas, inference_failure_on_function_invocation, unnecessary_parenthesis, unnecessary_raw_strings, unnecessary_null_checks, join_return_with_assignment, prefer_final_locals, avoid_js_rounded_ints, avoid_positional_boolean_parameters, always_specify_types

extension GetTrackingLogCollection on Isar {
  IsarCollection<TrackingLog> get trackingLogs => this.collection();
}

const TrackingLogSchema = CollectionSchema(
  name: r'TrackingLog',
  id: 6356144849888344593,
  properties: {
    r'fastId': PropertySchema(
      id: 0,
      name: r'fastId',
      type: IsarType.string,
    ),
    r'isMocked': PropertySchema(
      id: 1,
      name: r'isMocked',
      type: IsarType.bool,
    ),
    r'isSynced': PropertySchema(
      id: 2,
      name: r'isSynced',
      type: IsarType.bool,
    ),
    r'lastUpdatedAt': PropertySchema(
      id: 3,
      name: r'lastUpdatedAt',
      type: IsarType.dateTime,
    ),
    r'latitude': PropertySchema(
      id: 4,
      name: r'latitude',
      type: IsarType.double,
    ),
    r'longitude': PropertySchema(
      id: 5,
      name: r'longitude',
      type: IsarType.double,
    ),
    r'recordedAtMobile': PropertySchema(
      id: 6,
      name: r'recordedAtMobile',
      type: IsarType.dateTime,
    ),
    r'speed': PropertySchema(
      id: 7,
      name: r'speed',
      type: IsarType.double,
    ),
    r'userId': PropertySchema(
      id: 8,
      name: r'userId',
      type: IsarType.long,
    ),
    r'version': PropertySchema(
      id: 9,
      name: r'version',
      type: IsarType.long,
    )
  },
  estimateSize: _trackingLogEstimateSize,
  serialize: _trackingLogSerialize,
  deserialize: _trackingLogDeserialize,
  deserializeProp: _trackingLogDeserializeProp,
  idName: r'id',
  indexes: {
    r'fastId': IndexSchema(
      id: -8283197234171497501,
      name: r'fastId',
      unique: true,
      replace: true,
      properties: [
        IndexPropertySchema(
          name: r'fastId',
          type: IndexType.hash,
          caseSensitive: true,
        )
      ],
    )
  },
  links: {},
  embeddedSchemas: {},
  getId: _trackingLogGetId,
  getLinks: _trackingLogGetLinks,
  attach: _trackingLogAttach,
  version: '3.1.0+1',
);

int _trackingLogEstimateSize(
  TrackingLog object,
  List<int> offsets,
  Map<Type, List<int>> allOffsets,
) {
  var bytesCount = offsets.last;
  bytesCount += 3 + object.fastId.length * 3;
  return bytesCount;
}

void _trackingLogSerialize(
  TrackingLog object,
  IsarWriter writer,
  List<int> offsets,
  Map<Type, List<int>> allOffsets,
) {
  writer.writeString(offsets[0], object.fastId);
  writer.writeBool(offsets[1], object.isMocked);
  writer.writeBool(offsets[2], object.isSynced);
  writer.writeDateTime(offsets[3], object.lastUpdatedAt);
  writer.writeDouble(offsets[4], object.latitude);
  writer.writeDouble(offsets[5], object.longitude);
  writer.writeDateTime(offsets[6], object.recordedAtMobile);
  writer.writeDouble(offsets[7], object.speed);
  writer.writeLong(offsets[8], object.userId);
  writer.writeLong(offsets[9], object.version);
}

TrackingLog _trackingLogDeserialize(
  Id id,
  IsarReader reader,
  List<int> offsets,
  Map<Type, List<int>> allOffsets,
) {
  final object = TrackingLog();
  object.fastId = reader.readString(offsets[0]);
  object.id = id;
  object.isMocked = reader.readBool(offsets[1]);
  object.isSynced = reader.readBool(offsets[2]);
  object.lastUpdatedAt = reader.readDateTime(offsets[3]);
  object.latitude = reader.readDouble(offsets[4]);
  object.longitude = reader.readDouble(offsets[5]);
  object.recordedAtMobile = reader.readDateTime(offsets[6]);
  object.speed = reader.readDouble(offsets[7]);
  object.userId = reader.readLong(offsets[8]);
  object.version = reader.readLong(offsets[9]);
  return object;
}

P _trackingLogDeserializeProp<P>(
  IsarReader reader,
  int propertyId,
  int offset,
  Map<Type, List<int>> allOffsets,
) {
  switch (propertyId) {
    case 0:
      return (reader.readString(offset)) as P;
    case 1:
      return (reader.readBool(offset)) as P;
    case 2:
      return (reader.readBool(offset)) as P;
    case 3:
      return (reader.readDateTime(offset)) as P;
    case 4:
      return (reader.readDouble(offset)) as P;
    case 5:
      return (reader.readDouble(offset)) as P;
    case 6:
      return (reader.readDateTime(offset)) as P;
    case 7:
      return (reader.readDouble(offset)) as P;
    case 8:
      return (reader.readLong(offset)) as P;
    case 9:
      return (reader.readLong(offset)) as P;
    default:
      throw IsarError('Unknown property with id $propertyId');
  }
}

Id _trackingLogGetId(TrackingLog object) {
  return object.id;
}

List<IsarLinkBase<dynamic>> _trackingLogGetLinks(TrackingLog object) {
  return [];
}

void _trackingLogAttach(
    IsarCollection<dynamic> col, Id id, TrackingLog object) {
  object.id = id;
}

extension TrackingLogByIndex on IsarCollection<TrackingLog> {
  Future<TrackingLog?> getByFastId(String fastId) {
    return getByIndex(r'fastId', [fastId]);
  }

  TrackingLog? getByFastIdSync(String fastId) {
    return getByIndexSync(r'fastId', [fastId]);
  }

  Future<bool> deleteByFastId(String fastId) {
    return deleteByIndex(r'fastId', [fastId]);
  }

  bool deleteByFastIdSync(String fastId) {
    return deleteByIndexSync(r'fastId', [fastId]);
  }

  Future<List<TrackingLog?>> getAllByFastId(List<String> fastIdValues) {
    final values = fastIdValues.map((e) => [e]).toList();
    return getAllByIndex(r'fastId', values);
  }

  List<TrackingLog?> getAllByFastIdSync(List<String> fastIdValues) {
    final values = fastIdValues.map((e) => [e]).toList();
    return getAllByIndexSync(r'fastId', values);
  }

  Future<int> deleteAllByFastId(List<String> fastIdValues) {
    final values = fastIdValues.map((e) => [e]).toList();
    return deleteAllByIndex(r'fastId', values);
  }

  int deleteAllByFastIdSync(List<String> fastIdValues) {
    final values = fastIdValues.map((e) => [e]).toList();
    return deleteAllByIndexSync(r'fastId', values);
  }

  Future<Id> putByFastId(TrackingLog object) {
    return putByIndex(r'fastId', object);
  }

  Id putByFastIdSync(TrackingLog object, {bool saveLinks = true}) {
    return putByIndexSync(r'fastId', object, saveLinks: saveLinks);
  }

  Future<List<Id>> putAllByFastId(List<TrackingLog> objects) {
    return putAllByIndex(r'fastId', objects);
  }

  List<Id> putAllByFastIdSync(List<TrackingLog> objects,
      {bool saveLinks = true}) {
    return putAllByIndexSync(r'fastId', objects, saveLinks: saveLinks);
  }
}

extension TrackingLogQueryWhereSort
    on QueryBuilder<TrackingLog, TrackingLog, QWhere> {
  QueryBuilder<TrackingLog, TrackingLog, QAfterWhere> anyId() {
    return QueryBuilder.apply(this, (query) {
      return query.addWhereClause(const IdWhereClause.any());
    });
  }
}

extension TrackingLogQueryWhere
    on QueryBuilder<TrackingLog, TrackingLog, QWhereClause> {
  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> idEqualTo(Id id) {
    return QueryBuilder.apply(this, (query) {
      return query.addWhereClause(IdWhereClause.between(
        lower: id,
        upper: id,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> idNotEqualTo(
      Id id) {
    return QueryBuilder.apply(this, (query) {
      if (query.whereSort == Sort.asc) {
        return query
            .addWhereClause(
              IdWhereClause.lessThan(upper: id, includeUpper: false),
            )
            .addWhereClause(
              IdWhereClause.greaterThan(lower: id, includeLower: false),
            );
      } else {
        return query
            .addWhereClause(
              IdWhereClause.greaterThan(lower: id, includeLower: false),
            )
            .addWhereClause(
              IdWhereClause.lessThan(upper: id, includeUpper: false),
            );
      }
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> idGreaterThan(Id id,
      {bool include = false}) {
    return QueryBuilder.apply(this, (query) {
      return query.addWhereClause(
        IdWhereClause.greaterThan(lower: id, includeLower: include),
      );
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> idLessThan(Id id,
      {bool include = false}) {
    return QueryBuilder.apply(this, (query) {
      return query.addWhereClause(
        IdWhereClause.lessThan(upper: id, includeUpper: include),
      );
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> idBetween(
    Id lowerId,
    Id upperId, {
    bool includeLower = true,
    bool includeUpper = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addWhereClause(IdWhereClause.between(
        lower: lowerId,
        includeLower: includeLower,
        upper: upperId,
        includeUpper: includeUpper,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> fastIdEqualTo(
      String fastId) {
    return QueryBuilder.apply(this, (query) {
      return query.addWhereClause(IndexWhereClause.equalTo(
        indexName: r'fastId',
        value: [fastId],
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterWhereClause> fastIdNotEqualTo(
      String fastId) {
    return QueryBuilder.apply(this, (query) {
      if (query.whereSort == Sort.asc) {
        return query
            .addWhereClause(IndexWhereClause.between(
              indexName: r'fastId',
              lower: [],
              upper: [fastId],
              includeUpper: false,
            ))
            .addWhereClause(IndexWhereClause.between(
              indexName: r'fastId',
              lower: [fastId],
              includeLower: false,
              upper: [],
            ));
      } else {
        return query
            .addWhereClause(IndexWhereClause.between(
              indexName: r'fastId',
              lower: [fastId],
              includeLower: false,
              upper: [],
            ))
            .addWhereClause(IndexWhereClause.between(
              indexName: r'fastId',
              lower: [],
              upper: [fastId],
              includeUpper: false,
            ));
      }
    });
  }
}

extension TrackingLogQueryFilter
    on QueryBuilder<TrackingLog, TrackingLog, QFilterCondition> {
  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> fastIdEqualTo(
    String value, {
    bool caseSensitive = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'fastId',
        value: value,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      fastIdGreaterThan(
    String value, {
    bool include = false,
    bool caseSensitive = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'fastId',
        value: value,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> fastIdLessThan(
    String value, {
    bool include = false,
    bool caseSensitive = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'fastId',
        value: value,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> fastIdBetween(
    String lower,
    String upper, {
    bool includeLower = true,
    bool includeUpper = true,
    bool caseSensitive = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'fastId',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      fastIdStartsWith(
    String value, {
    bool caseSensitive = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.startsWith(
        property: r'fastId',
        value: value,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> fastIdEndsWith(
    String value, {
    bool caseSensitive = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.endsWith(
        property: r'fastId',
        value: value,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> fastIdContains(
      String value,
      {bool caseSensitive = true}) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.contains(
        property: r'fastId',
        value: value,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> fastIdMatches(
      String pattern,
      {bool caseSensitive = true}) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.matches(
        property: r'fastId',
        wildcard: pattern,
        caseSensitive: caseSensitive,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      fastIdIsEmpty() {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'fastId',
        value: '',
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      fastIdIsNotEmpty() {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        property: r'fastId',
        value: '',
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> idEqualTo(
      Id value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'id',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> idGreaterThan(
    Id value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'id',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> idLessThan(
    Id value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'id',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> idBetween(
    Id lower,
    Id upper, {
    bool includeLower = true,
    bool includeUpper = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'id',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> isMockedEqualTo(
      bool value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'isMocked',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> isSyncedEqualTo(
      bool value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'isSynced',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      lastUpdatedAtEqualTo(DateTime value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'lastUpdatedAt',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      lastUpdatedAtGreaterThan(
    DateTime value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'lastUpdatedAt',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      lastUpdatedAtLessThan(
    DateTime value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'lastUpdatedAt',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      lastUpdatedAtBetween(
    DateTime lower,
    DateTime upper, {
    bool includeLower = true,
    bool includeUpper = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'lastUpdatedAt',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> latitudeEqualTo(
    double value, {
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'latitude',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      latitudeGreaterThan(
    double value, {
    bool include = false,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'latitude',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      latitudeLessThan(
    double value, {
    bool include = false,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'latitude',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> latitudeBetween(
    double lower,
    double upper, {
    bool includeLower = true,
    bool includeUpper = true,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'latitude',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      longitudeEqualTo(
    double value, {
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'longitude',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      longitudeGreaterThan(
    double value, {
    bool include = false,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'longitude',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      longitudeLessThan(
    double value, {
    bool include = false,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'longitude',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      longitudeBetween(
    double lower,
    double upper, {
    bool includeLower = true,
    bool includeUpper = true,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'longitude',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      recordedAtMobileEqualTo(DateTime value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'recordedAtMobile',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      recordedAtMobileGreaterThan(
    DateTime value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'recordedAtMobile',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      recordedAtMobileLessThan(
    DateTime value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'recordedAtMobile',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      recordedAtMobileBetween(
    DateTime lower,
    DateTime upper, {
    bool includeLower = true,
    bool includeUpper = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'recordedAtMobile',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> speedEqualTo(
    double value, {
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'speed',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      speedGreaterThan(
    double value, {
    bool include = false,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'speed',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> speedLessThan(
    double value, {
    bool include = false,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'speed',
        value: value,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> speedBetween(
    double lower,
    double upper, {
    bool includeLower = true,
    bool includeUpper = true,
    double epsilon = Query.epsilon,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'speed',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
        epsilon: epsilon,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> userIdEqualTo(
      int value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'userId',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      userIdGreaterThan(
    int value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'userId',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> userIdLessThan(
    int value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'userId',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> userIdBetween(
    int lower,
    int upper, {
    bool includeLower = true,
    bool includeUpper = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'userId',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> versionEqualTo(
      int value) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.equalTo(
        property: r'version',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition>
      versionGreaterThan(
    int value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.greaterThan(
        include: include,
        property: r'version',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> versionLessThan(
    int value, {
    bool include = false,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.lessThan(
        include: include,
        property: r'version',
        value: value,
      ));
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterFilterCondition> versionBetween(
    int lower,
    int upper, {
    bool includeLower = true,
    bool includeUpper = true,
  }) {
    return QueryBuilder.apply(this, (query) {
      return query.addFilterCondition(FilterCondition.between(
        property: r'version',
        lower: lower,
        includeLower: includeLower,
        upper: upper,
        includeUpper: includeUpper,
      ));
    });
  }
}

extension TrackingLogQueryObject
    on QueryBuilder<TrackingLog, TrackingLog, QFilterCondition> {}

extension TrackingLogQueryLinks
    on QueryBuilder<TrackingLog, TrackingLog, QFilterCondition> {}

extension TrackingLogQuerySortBy
    on QueryBuilder<TrackingLog, TrackingLog, QSortBy> {
  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByFastId() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'fastId', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByFastIdDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'fastId', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByIsMocked() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isMocked', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByIsMockedDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isMocked', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByIsSynced() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isSynced', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByIsSyncedDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isSynced', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByLastUpdatedAt() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'lastUpdatedAt', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy>
      sortByLastUpdatedAtDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'lastUpdatedAt', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByLatitude() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'latitude', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByLatitudeDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'latitude', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByLongitude() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'longitude', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByLongitudeDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'longitude', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy>
      sortByRecordedAtMobile() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'recordedAtMobile', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy>
      sortByRecordedAtMobileDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'recordedAtMobile', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortBySpeed() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'speed', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortBySpeedDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'speed', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByUserId() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'userId', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByUserIdDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'userId', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByVersion() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'version', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> sortByVersionDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'version', Sort.desc);
    });
  }
}

extension TrackingLogQuerySortThenBy
    on QueryBuilder<TrackingLog, TrackingLog, QSortThenBy> {
  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByFastId() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'fastId', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByFastIdDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'fastId', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenById() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'id', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByIdDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'id', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByIsMocked() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isMocked', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByIsMockedDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isMocked', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByIsSynced() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isSynced', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByIsSyncedDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'isSynced', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByLastUpdatedAt() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'lastUpdatedAt', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy>
      thenByLastUpdatedAtDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'lastUpdatedAt', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByLatitude() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'latitude', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByLatitudeDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'latitude', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByLongitude() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'longitude', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByLongitudeDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'longitude', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy>
      thenByRecordedAtMobile() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'recordedAtMobile', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy>
      thenByRecordedAtMobileDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'recordedAtMobile', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenBySpeed() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'speed', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenBySpeedDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'speed', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByUserId() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'userId', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByUserIdDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'userId', Sort.desc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByVersion() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'version', Sort.asc);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QAfterSortBy> thenByVersionDesc() {
    return QueryBuilder.apply(this, (query) {
      return query.addSortBy(r'version', Sort.desc);
    });
  }
}

extension TrackingLogQueryWhereDistinct
    on QueryBuilder<TrackingLog, TrackingLog, QDistinct> {
  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByFastId(
      {bool caseSensitive = true}) {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'fastId', caseSensitive: caseSensitive);
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByIsMocked() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'isMocked');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByIsSynced() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'isSynced');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByLastUpdatedAt() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'lastUpdatedAt');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByLatitude() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'latitude');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByLongitude() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'longitude');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct>
      distinctByRecordedAtMobile() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'recordedAtMobile');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctBySpeed() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'speed');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByUserId() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'userId');
    });
  }

  QueryBuilder<TrackingLog, TrackingLog, QDistinct> distinctByVersion() {
    return QueryBuilder.apply(this, (query) {
      return query.addDistinctBy(r'version');
    });
  }
}

extension TrackingLogQueryProperty
    on QueryBuilder<TrackingLog, TrackingLog, QQueryProperty> {
  QueryBuilder<TrackingLog, int, QQueryOperations> idProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'id');
    });
  }

  QueryBuilder<TrackingLog, String, QQueryOperations> fastIdProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'fastId');
    });
  }

  QueryBuilder<TrackingLog, bool, QQueryOperations> isMockedProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'isMocked');
    });
  }

  QueryBuilder<TrackingLog, bool, QQueryOperations> isSyncedProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'isSynced');
    });
  }

  QueryBuilder<TrackingLog, DateTime, QQueryOperations>
      lastUpdatedAtProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'lastUpdatedAt');
    });
  }

  QueryBuilder<TrackingLog, double, QQueryOperations> latitudeProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'latitude');
    });
  }

  QueryBuilder<TrackingLog, double, QQueryOperations> longitudeProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'longitude');
    });
  }

  QueryBuilder<TrackingLog, DateTime, QQueryOperations>
      recordedAtMobileProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'recordedAtMobile');
    });
  }

  QueryBuilder<TrackingLog, double, QQueryOperations> speedProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'speed');
    });
  }

  QueryBuilder<TrackingLog, int, QQueryOperations> userIdProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'userId');
    });
  }

  QueryBuilder<TrackingLog, int, QQueryOperations> versionProperty() {
    return QueryBuilder.apply(this, (query) {
      return query.addPropertyName(r'version');
    });
  }
}
