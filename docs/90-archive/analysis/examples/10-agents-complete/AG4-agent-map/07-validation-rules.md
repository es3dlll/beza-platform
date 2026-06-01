# 07 - قواعد التحقق (Validation Rules)

## Form Request: NearbyAgentsRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NearbyAgentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
                function ($attribute, $value, $fail) {
                    if ($value < -90 || $value > 90) {
                        $fail('خط العرض يجب أن يكون بين -90 و 90 درجة.');
                    }
                },
            ],

            'lng' => [
                'required',
                'numeric',
                'between:-180,180',
                function ($attribute, $value, $fail) {
                    if ($value < -180 || $value > 180) {
                        $fail('خط الطول يجب أن يكون بين -180 و 180 درجة.');
                    }
                },
            ],

            'radius' => [
                'required',
                'integer',
                'min:1',
                'max:50',
            ],

            'service_type' => [
                'nullable',
                'string',
                'in:cash_in,cash_out,transfer,all',
            ],

            'is_online' => [
                'nullable',
                'boolean',
            ],

            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'حقل خط العرض مطلوب.',
            'lat.numeric' => 'خط العرض يجب أن يكون رقماً.',
            'lat.between' => 'خط العرض يجب أن يكون بين -90 و 90 درجة.',

            'lng.required' => 'حقل خط الطول مطلوب.',
            'lng.numeric' => 'خط الطول يجب أن يكون رقماً.',
            'lng.between' => 'خط الطول يجب أن يكون بين -180 و 180 درجة.',

            'radius.required' => 'حقل نصف القطر مطلوب.',
            'radius.integer' => 'نصف القطر يجب أن يكون رقماً صحيحاً.',
            'radius.min' => 'الحد الأدنى لنصف القطر هو 1 كم.',
            'radius.max' => 'الحد الأقصى لنصف القطر هو 50 كم.',

            'service_type.in' => 'نوع الخدمة غير مدعوم. القيم المسموحة: cash_in, cash_out, transfer, all.',

            'limit.integer' => 'الحد الأقصى للنتائج يجب أن يكون رقماً صحيحاً.',
            'limit.min' => 'الحد الأدنى للنتائج هو 1.',
            'limit.max' => 'الحد الأقصى للنتائج هو 100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lat' => (float) $this->input('lat'),
            'lng' => (float) $this->input('lng'),
            'radius' => (int) ($this->input('radius', 10)),
        ]);
    }
}
```

## Form Request: UpdateAgentLocationRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgent();
    }

    public function rules(): array
    {
        return [
            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'lng' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'accuracy' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000',
            ],

            'source' => [
                'nullable',
                'string',
                'in:gps,ip,manual',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'خط العرض مطلوب.',
            'lat.between' => 'خط العرض غير صالح.',
            'lng.required' => 'خط الطول مطلوب.',
            'lng.between' => 'خط الطول غير صالح.',
            'accuracy.max' => 'دقة الموقع غير صالحة (حد أقصى 10000 متر).',
            'source.in' => 'مصدر الموقع غير صالح.',
        ];
    }
}
```

## التحقق التجاري (Business Validation)

```php
<?php

namespace App\Services\Validators;

use App\Exceptions\InvalidCoordinatesException;
use App\Exceptions\NoAgentsNearbyException;

class MapQueryValidator
{
    /**
     * التحقق من صحة الإحداثيات للموقع السوري
     */
    public function validateSyrianCoordinates(float $lat, float $lng): void
    {
        // حدود سورية التقريبية
        $syriaBounds = [
            'lat' => ['min' => 32.0, 'max' => 37.5],
            'lng' => ['min' => 35.5, 'max' => 42.5],
        ];

        if ($lat < $syriaBounds['lat']['min'] || $lat > $syriaBounds['lat']['max']) {
            throw new InvalidCoordinatesException(
                'خط العرض خارج النطاق المتوقع لسورية (32.0 - 37.5).'
            );
        }

        if ($lng < $syriaBounds['lng']['min'] || $lng > $syriaBounds['lng']['max']) {
            throw new InvalidCoordinatesException(
                'خط الطول خارج النطاق المتوقع لسورية (35.5 - 42.5).'
            );
        }
    }

    /**
     * التحقق من وجود نتائج
     */
    public function validateResults(array $agents): void
    {
        if (empty($agents)) {
            throw new NoAgentsNearbyException(
                'لا يوجد وكلاء متاحون ضمن نصف القطر المحدد.'
            );
        }
    }

    /**
     * التحقق من نصف القطر حسب الخدمة
     */
    public function validateRadiusForServiceType(int $radiusKm, ?string $serviceType): void
    {
        // بعض الخدمات تتطلب نصف قطر أكبر
        $minRadius = match ($serviceType) {
            'cash_out' => 3,
            'transfer' => 5,
            default => 1,
        };

        if ($radiusKm < $minRadius) {
            throw new \InvalidArgumentException(
                "نصف القطر الأدنى لخدمة {$serviceType} هو {$minRadius} كم."
            );
        }
    }

    /**
     * تحديد عدد النتائج حسب الكثافة
     */
    public function getAdjustedLimit(int $requestedLimit, int $totalFound): int
    {
        // في المناطق الكثيفة، نحدد النتائج
        if ($totalFound > 50) {
            return min($requestedLimit, 30);
        }

        return min($requestedLimit, 100);
    }
}
```

## ملخص قواعد التحقق

| الحقل | مطلوب | النوع | المدى | ملاحظات |
|-------|-------|-------|-------|---------|
| lat | نعم | numeric | -90 .. 90 | يفضل 32.0-37.5 لسورية |
| lng | نعم | numeric | -180 .. 180 | يفضل 35.5-42.5 لسورية |
| radius | نعم | integer | 1 .. 50 km | km |
| service_type | لا | string | cash_in, cash_out, transfer, all | فلتر اختياري |
| is_online | لا | boolean | true/false | فلتر حالة الاتصال |
| limit | لا | integer | 1 .. 100 | عدد النتائج |
