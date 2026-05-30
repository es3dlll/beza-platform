import '../../../core/api/api_client.dart';

class ProfileDetails {
  final String? fullName;
  final String? fullNameAr;
  final String? nationalId;
  final String? dateOfBirth;
  final String? gender;
  final String? address;
  final String? city;
  final String? province;

  ProfileDetails({
    this.fullName,
    this.fullNameAr,
    this.nationalId,
    this.dateOfBirth,
    this.gender,
    this.address,
    this.city,
    this.province,
  });

  factory ProfileDetails.fromJson(Map<String, dynamic> json) {
    return ProfileDetails(
      fullName: json['full_name'] as String?,
      fullNameAr: json['full_name_ar'] as String?,
      nationalId: json['national_id'] as String?,
      dateOfBirth: json['date_of_birth'] as String?,
      gender: json['gender'] as String?,
      address: json['address'] as String?,
      city: json['city'] as String?,
      province: json['province'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    if (fullName != null) map['full_name'] = fullName;
    if (fullNameAr != null) map['full_name_ar'] = fullNameAr;
    if (nationalId != null) map['national_id'] = nationalId;
    if (dateOfBirth != null) map['date_of_birth'] = dateOfBirth;
    if (gender != null) map['gender'] = gender;
    if (address != null) map['address'] = address;
    if (city != null) map['city'] = city;
    if (province != null) map['province'] = province;
    return map;
  }
}

class UserProfile {
  final int id;
  final String phone;
  final String? email;
  final String status;
  final int kycTier;
  final DateTime createdAt;
  final ProfileDetails? profile;

  UserProfile({
    required this.id,
    required this.phone,
    this.email,
    required this.status,
    required this.kycTier,
    required this.createdAt,
    this.profile,
  });

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    final profileJson = json['profile'] as Map<String, dynamic>?;
    return UserProfile(
      id: json['id'] as int,
      phone: json['phone'] as String? ?? '',
      email: json['email'] as String?,
      status: json['status'] as String? ?? 'active',
      kycTier: json['kyc_tier'] as int? ?? 0,
      createdAt: DateTime.parse(json['created_at'] as String).toLocal(),
      profile: profileJson != null
          ? ProfileDetails.fromJson(profileJson)
          : null,
    );
  }

  String get displayName => profile?.fullNameAr ?? profile?.fullName ?? phone;
}

class ProfileService {
  final ApiClient _client;

  ProfileService(this._client);

  Future<UserProfile> getProfile() async {
    final response = await _client.get('/identity/profile');
    final data = response.data;
    final userJson = (data['data'] as Map<String, dynamic>?) ?? data;
    return UserProfile.fromJson(userJson);
  }

  Future<UserProfile> updateProfile(Map<String, dynamic> fields) async {
    final response = await _client.put('/identity/profile', data: fields);
    final data = response.data;
    final userJson = ((data['data'] as Map<String, dynamic>?) ?? data)['user'] as Map<String, dynamic>? ?? data;
    return UserProfile.fromJson(userJson);
  }

  Future<void> changePin({
    required String currentPin,
    required String newPin,
    required String newPinConfirmation,
  }) async {
    await _client.post('/auth/pin/change', data: {
      'current_pin': currentPin,
      'new_pin': newPin,
      'new_pin_confirmation': newPinConfirmation,
    });
  }
}
