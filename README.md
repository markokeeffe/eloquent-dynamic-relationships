# Eloquent Dynamic Relationships
Dynamic relationships for Laravel Eloquent models using sub-queries to obtain foreign key values with custom conditions.

Inspired by this post: https://reinink.ca/articles/dynamic-relationships-in-laravel-using-subqueries

This is useful when you have a "1 -> Many" relationship in your system e.g. `User->logins` and you want to define an Eloquent relationship to resolve in a "1 -> 1" fashion using some condition e.g. `User->lastLogin`.

## Usage
1. Apply the `\Markok\Eloquent\Traits\BelongsToDynamic` trait to your model.
2. Define your relationship e.g.

```php
class User extends Model
{
    use \Markok\Eloquent\Traits\BelongsToDynamic;
    
    // Normal "1 -> Many"
    public function logins()
    {
        return $this->hasMany(Login::class);
    }
    
    // Behaves like "1 -> 1", retrieves the 'latest' login for this user
    public function lastLogin()
    {
        $subQuery = Login::select('id')->latest();
        return $this->belongsToDynamic(Login::class, $subQuery, 'id', 'user_id');
    }
}
```