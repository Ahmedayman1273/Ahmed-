<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (
            User::where('email', $row['email'])->exists() ||
            User::where('id', $row['id'])->exists()
        ) {
            return null;
        }

        return new User([
    'id'             => $row['id'], // ← لازم تكون موجودة هنا
    'name'           => $row['name'],
    'email'          => $row['email'],
    'personal_email' => $row['personal_email'] ?? '',
    'phone_number'   => $row['phone_number'],
    'type'           => $row['type'],
    'major'          => $row['major'] ?? 'Computer Science',
    'password'       => Hash::make($row['password'] ?? '123456'),
]);

    }
}
