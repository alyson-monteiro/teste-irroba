<?php

namespace App\Enums;

enum ProductUpdateType: string
{
    case Stock = 'stock';
    case Price = 'price';
    case Description = 'description';
    case Images = 'images';
    case Tags = 'tags';
}
