<?php

// Azerbaijani messages for the validation rules the API uses.
return [
    'array' => ':attribute siyahı olmalıdır.',
    'between' => ['numeric' => ':attribute :min ilə :max arasında olmalıdır.'],
    'boolean' => ':attribute doğru və ya yanlış olmalıdır.',
    'email' => ':attribute düzgün e-poçt ünvanı olmalıdır.',
    'enum' => 'Seçilmiş :attribute yanlışdır.',
    'exists' => 'Seçilmiş :attribute mövcud deyil.',
    'image' => ':attribute şəkil olmalıdır.',
    'in' => 'Seçilmiş :attribute yanlışdır.',
    'integer' => ':attribute tam ədəd olmalıdır.',
    'max' => [
        'array' => ':attribute :max elementdən çox ola bilməz.',
        'file' => ':attribute :max kilobaytdan böyük ola bilməz.',
        'numeric' => ':attribute :max-dan böyük ola bilməz.',
        'string' => ':attribute :max simvoldan uzun ola bilməz.',
    ],
    'min' => [
        'numeric' => ':attribute ən azı :min olmalıdır.',
        'string' => ':attribute ən azı :min simvol olmalıdır.',
    ],
    'numeric' => ':attribute rəqəm olmalıdır.',
    'prohibited' => ':attribute sahəsi boş qalmalıdır.',
    'required' => ':attribute sahəsi mütləqdir.',
    'string' => ':attribute mətn olmalıdır.',
    'url' => ':attribute düzgün link olmalıdır.',

    'attributes' => [
        'name' => 'ad', 'author_name' => 'ad', 'rating' => 'qiymət', 'body' => 'rəy',
        'email' => 'e-poçt', 'phone' => 'telefon', 'website' => 'veb sayt', 'message' => 'mesaj',
        'region_id' => 'region', 'district_id' => 'rayon', 'description' => 'təsvir', 'difficulty' => 'çətinlik',
        'route_type' => 'marşrut növü', 'distance_km' => 'məsafə', 'duration_minutes' => 'müddət',
        'lat' => 'enlik', 'lng' => 'uzunluq', 'photos' => 'şəkillər', 'submitter_name' => 'ad',
        'submitter_email' => 'e-poçt', 'kind' => 'növ', 'regions' => 'regionlar', 'languages' => 'dillər',
    ],
];
