<?php
return [
    'roles' => ['kaprodi', 'dosen', 'mahasiswa'],
    'permissions' => [
        'kaprodi' => ['users.php'],
        'dosen' => ['nilai.php'],
        'mahasiswa' => ['krs.php']
    ]
];
