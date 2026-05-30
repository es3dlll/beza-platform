class CardModel {
  final String id;
  final String userId;
  final String cardType;
  final String status;
  final String cardholderName;
  final String cardNumberLast4;
  final String expiryMonth;
  final String expiryYear;
  final String currency;
  final int dailyLimit;
  final int weeklyLimit;
  final int monthlyLimit;
  final int dailyUsed;
  final int weeklyUsed;
  final int monthlyUsed;
  final int singleTxnLimit;
  final bool isVirtual;
  final bool internationalEnabled;
  final bool atmEnabled;
  final bool contactlessEnabled;
  final bool ecommerceEnabled;
  final String? activatedAt;
  final String? suspendedAt;
  final String? expiresAt;
  final String? createdAt;
  final String? updatedAt;

  const CardModel({
    required this.id,
    required this.userId,
    required this.cardType,
    required this.status,
    required this.cardholderName,
    required this.cardNumberLast4,
    required this.expiryMonth,
    required this.expiryYear,
    this.currency = 'SYP',
    this.dailyLimit = 0,
    this.weeklyLimit = 0,
    this.monthlyLimit = 0,
    this.dailyUsed = 0,
    this.weeklyUsed = 0,
    this.monthlyUsed = 0,
    this.singleTxnLimit = 0,
    this.isVirtual = false,
    this.internationalEnabled = false,
    this.atmEnabled = false,
    this.contactlessEnabled = false,
    this.ecommerceEnabled = false,
    this.activatedAt,
    this.suspendedAt,
    this.expiresAt,
    this.createdAt,
    this.updatedAt,
  });

  factory CardModel.fromJson(Map<String, dynamic> json) {
    return CardModel(
      id: json['id'] as String,
      userId: json['user_id'] as String,
      cardType: json['card_type'] as String,
      status: json['status'] as String,
      cardholderName: json['cardholder_name'] as String,
      cardNumberLast4: json['card_number_last4'] as String,
      expiryMonth: json['expiry_month'] as String,
      expiryYear: json['expiry_year'] as String,
      currency: json['currency'] as String? ?? 'SYP',
      dailyLimit: json['daily_limit'] as int? ?? 0,
      weeklyLimit: json['weekly_limit'] as int? ?? 0,
      monthlyLimit: json['monthly_limit'] as int? ?? 0,
      dailyUsed: json['daily_used'] as int? ?? 0,
      weeklyUsed: json['weekly_used'] as int? ?? 0,
      monthlyUsed: json['monthly_used'] as int? ?? 0,
      singleTxnLimit: json['single_txn_limit'] as int? ?? 0,
      isVirtual: json['is_virtual'] as bool? ?? false,
      internationalEnabled: json['international_enabled'] as bool? ?? false,
      atmEnabled: json['atm_enabled'] as bool? ?? false,
      contactlessEnabled: json['contactless_enabled'] as bool? ?? false,
      ecommerceEnabled: json['ecommerce_enabled'] as bool? ?? false,
      activatedAt: json['activated_at'] as String?,
      suspendedAt: json['suspended_at'] as String?,
      expiresAt: json['expires_at'] as String?,
      createdAt: json['created_at'] as String?,
      updatedAt: json['updated_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'user_id': userId,
        'card_type': cardType,
        'status': status,
        'cardholder_name': cardholderName,
        'card_number_last4': cardNumberLast4,
        'expiry_month': expiryMonth,
        'expiry_year': expiryYear,
        'currency': currency,
        'daily_limit': dailyLimit,
        'weekly_limit': weeklyLimit,
        'monthly_limit': monthlyLimit,
        'daily_used': dailyUsed,
        'weekly_used': weeklyUsed,
        'monthly_used': monthlyUsed,
        'single_txn_limit': singleTxnLimit,
        'is_virtual': isVirtual,
        'international_enabled': internationalEnabled,
        'atm_enabled': atmEnabled,
        'contactless_enabled': contactlessEnabled,
        'ecommerce_enabled': ecommerceEnabled,
        'activated_at': activatedAt,
        'suspended_at': suspendedAt,
        'expires_at': expiresAt,
        'created_at': createdAt,
        'updated_at': updatedAt,
      };

  CardModel copyWith({
    String? id,
    String? userId,
    String? cardType,
    String? status,
    String? cardholderName,
    String? cardNumberLast4,
    String? expiryMonth,
    String? expiryYear,
    String? currency,
    int? dailyLimit,
    int? weeklyLimit,
    int? monthlyLimit,
    int? dailyUsed,
    int? weeklyUsed,
    int? monthlyUsed,
    int? singleTxnLimit,
    bool? isVirtual,
    bool? internationalEnabled,
    bool? atmEnabled,
    bool? contactlessEnabled,
    bool? ecommerceEnabled,
    String? activatedAt,
    String? suspendedAt,
    String? expiresAt,
    String? createdAt,
    String? updatedAt,
  }) =>
      CardModel(
        id: id ?? this.id,
        userId: userId ?? this.userId,
        cardType: cardType ?? this.cardType,
        status: status ?? this.status,
        cardholderName: cardholderName ?? this.cardholderName,
        cardNumberLast4: cardNumberLast4 ?? this.cardNumberLast4,
        expiryMonth: expiryMonth ?? this.expiryMonth,
        expiryYear: expiryYear ?? this.expiryYear,
        currency: currency ?? this.currency,
        dailyLimit: dailyLimit ?? this.dailyLimit,
        weeklyLimit: weeklyLimit ?? this.weeklyLimit,
        monthlyLimit: monthlyLimit ?? this.monthlyLimit,
        dailyUsed: dailyUsed ?? this.dailyUsed,
        weeklyUsed: weeklyUsed ?? this.weeklyUsed,
        monthlyUsed: monthlyUsed ?? this.monthlyUsed,
        singleTxnLimit: singleTxnLimit ?? this.singleTxnLimit,
        isVirtual: isVirtual ?? this.isVirtual,
        internationalEnabled: internationalEnabled ?? this.internationalEnabled,
        atmEnabled: atmEnabled ?? this.atmEnabled,
        contactlessEnabled: contactlessEnabled ?? this.contactlessEnabled,
        ecommerceEnabled: ecommerceEnabled ?? this.ecommerceEnabled,
        activatedAt: activatedAt ?? this.activatedAt,
        suspendedAt: suspendedAt ?? this.suspendedAt,
        expiresAt: expiresAt ?? this.expiresAt,
        createdAt: createdAt ?? this.createdAt,
        updatedAt: updatedAt ?? this.updatedAt,
      );
}
