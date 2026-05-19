<?php

return [
    'modulos' => [
        'usuarios' => [
            'acciones' => ['ver', 'crear', 'editar', 'eliminar'],
            'descripcion' => 'Gestión de usuarios',
        ],
        'roles' => [
            'acciones' => ['ver', 'crear', 'editar', 'eliminar', 'asignar_permisos'],
            'descripcion' => 'Gestión de roles',
        ],
        'inventario' => [
            'acciones' => ['ver', 'crear', 'editar', 'eliminar'],
            'descripcion' => 'Gestión de inventario',
        ],
        'administracion' => [
            'acciones' => ['ver', 'crear', 'editar', 'eliminar'],
            'descripcion' => 'Gestión de administración',
        ],
    ],
];