<?php

return [
    [
        'name' => 'Marketplaces',
        'flag' => 'marketplace.index',
    ],

    [
        'name' => 'Stores',
        'flag' => 'marketplace.store.index',
    ],
    [
        'name'        => 'Create',
        'flag'        => 'marketplace.store.create',
        'parent_flag' => 'marketplace.store.index',
    ],
    [
        'name'        => 'Edit',
        'flag'        => 'marketplace.store.edit',
        'parent_flag' => 'marketplace.store.index',
    ],
    [
        'name'        => 'Delete',
        'flag'        => 'marketplace.store.destroy',
        'parent_flag' => 'marketplace.store.index',
    ],


];
