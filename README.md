# IN Development!!!


Laravel 11 Microservice Structure/Skeleton

- Laravel 11
- PHP 8.3
- MySql
- Traefik (Reverse Proxy, ApiGateway, LoadBalancer)


- Auth:
    - https://spatie.be/docs/laravel-permission/
    - php artisan app:install-roles-and-permissions
    - do not forget add the same JWT_SECRET to all microservices. This key must be generate EXCLUSIVELLY BY THE  auth Microservice, and set the same private key to all env variables from all microservices. 

## Routes and Middlewares
In the system there are Roles and Permissions. Permissions somehow are usually  linked to a Role. For example, a Role CompanyAdmin (Role) can Read, Write, Delete and Execute but an User can  only Read and Write. 

For controlling URLs and actions:

As example:
```php
    Route::get('/list', [CustomersController::class, 'actionCustomersList'])->middleware('JwtMiddleware:CompanyAdmin:read|execute||SuperAdmin:write,SuperAdmin,read|execute');
```

Notice: ('JwtMiddleware:CompanyAdmin:read|execute||SuperAdmin:write,SuperAdmin,execute') where:

- JwtMiddleware: Middleware
- CompanyAdmin:read|execute||SuperAdmin:write are the Role:Permissions(| separator between permissions and || between blocks)
- SuperAdmin is the Role that can access to the URL
- read|execute is the permission (independent from role) that is able to access to the url

```php
Notice: ('[MIDDLEWARE]:[Role:Permissions BLOCK],[Role Block],[Permission Block]')
```

For ignoring a Block, just leave it empty or add "-"

Comma separator between Blocks. 


