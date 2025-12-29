# Caching & Queueing Documentation

This document provides a comprehensive analysis of the caching and queueing implementations in the 10MG Backend codebase.

## Caching Implementation

### Current Caching Status

#### Cache Configuration
**Location**: `config/cache.php`

- **Default Driver**: Database cache (`CACHE_STORE=database`)
- **Available Drivers**: Array, Database, File, Memcached, Redis, DynamoDB, Octane
- **Cache Prefix**: `{app_name}_cache_` (configurable via `CACHE_PREFIX`)

#### Cache Drivers Available

1. **Database Cache** (Default)
   - Table: `cache` (configurable via `DB_CACHE_TABLE`)
   - Connection: Configurable via `DB_CACHE_CONNECTION`
   - Lock Table: `cache_locks` (configurable)

2. **Redis Cache** (Configured but not used)
   - Connection: `REDIS_CACHE_CONNECTION` (defaults to 'cache')
   - Lock Connection: `REDIS_CACHE_LOCK_CONNECTION` (defaults to 'default')

3. **File Cache**
   - Path: `storage/framework/cache/data`

4. **Memcached**
   - Host: `MEMCACHED_HOST` (defaults to 127.0.0.1)
   - Port: `MEMCACHED_PORT` (defaults to 11211)

### Current Cache Usage

#### ✅ Implemented Caching

1. **Visitor Counting Cache**
   - **Location**: `app/Http/Middleware/StoreVisitorCountMiddleware.php`
   - **Purpose**: Prevents duplicate visitor counting
   - **Cache Key**: `visitor_recorded_{date}_{unique_key}`
   - **Expiration**: End of day
   - **Implementation**:
   ```php
   $cache_key = "visitor_recorded_{$date}_{$unique_key}";
   if (!cache()->has($cache_key)) {
       cache()->put($cache_key, true, $expiresAt);
       // Increment visitor count
   }
   ```

2. **Settings Cache** (Configured but disabled)
   - **Location**: `config/settings.php`
   - **Status**: Disabled by default (`SETTINGS_CACHE_ENABLED=false`)
   - **Purpose**: Cache application settings
   - **Recommendation**: Enable for better performance

3. **Laravel Data Structure Cache**
   - **Location**: `config/data.php`
   - **Status**: Enabled
   - **Purpose**: Cache data object structures
   - **Store**: Uses default cache store

#### ❌ Missing Caching Opportunities

1. **Query Result Caching**
   - No caching of frequently accessed database queries
   - No caching of user data
   - No caching of product data
   - No caching of category data

2. **API Response Caching**
   - No caching of API responses
   - No HTTP caching headers (ETag, Last-Modified)
   - No cache-control headers

3. **Model Caching**
   - No model caching
   - No relationship caching
   - No attribute caching

4. **Configuration Caching**
   - No caching of configuration values
   - No caching of route data
   - No caching of view data

### Cache Implementation Recommendations

#### 1. Query Result Caching

**Example Implementation**:
```php
// Cache user data
$user = Cache::remember("user.{$id}", 3600, function() use ($id) {
    return User::with('businesses', 'roles')->find($id);
});

// Cache product data
$products = Cache::remember("products.category.{$categoryId}", 1800, function() use ($categoryId) {
    return EcommerceProduct::where('category_id', $categoryId)
        ->with(['rating', 'reviews'])
        ->get();
});
```

**Recommendations**:
- Cache frequently accessed queries
- Use appropriate TTL values
- Implement cache invalidation on updates
- Use cache tags for related data

#### 2. API Response Caching

**Example Implementation**:
```php
// Cache API responses
$response = Cache::remember("api.products.{$page}", 300, function() {
    return ProductResource::collection(
        EcommerceProduct::paginate(20)
    );
});

// Add cache headers
return response($response)
    ->header('Cache-Control', 'public, max-age=300')
    ->header('ETag', md5($response));
```

**Recommendations**:
- Cache GET endpoints
- Use HTTP caching headers
- Implement ETag support
- Consider Vary headers

#### 3. Model Caching

**Recommendations**:
- Use Laravel's cache remember pattern
- Consider model caching packages (e.g., `genealabs/laravel-model-caching`)
- Cache relationships
- Cache computed attributes

#### 4. Redis Implementation

**Current Status**: Redis is configured but not used

**Recommendations**:
- Switch from database cache to Redis
- Use Redis for session storage
- Use Redis for queue management
- Implement Redis for real-time features

**Configuration**:
```env
CACHE_STORE=redis
REDIS_CACHE_CONNECTION=cache
```

## Queueing Implementation

### Current Queue Status

#### Queue Configuration
**Location**: `config/queue.php`

- **Default Connection**: Database queue (`QUEUE_CONNECTION=database`)
- **Available Drivers**: Sync, Database, Beanstalkd, SQS, Redis
- **Queue Table**: `jobs` (configurable via `DB_QUEUE_TABLE`)
- **Failed Jobs**: Database UUIDs (configurable)

#### Queue Drivers Available

1. **Database Queue** (Default)
   - Table: `jobs`
   - Connection: Configurable via `DB_QUEUE_CONNECTION`
   - Retry After: 90 seconds (configurable)

2. **Redis Queue** (Configured but not used)
   - Connection: `REDIS_QUEUE_CONNECTION` (defaults to 'default')
   - Queue: `REDIS_QUEUE` (defaults to 'default')
   - Retry After: 90 seconds (configurable)

3. **SQS Queue** (Configured)
   - Region: `AWS_DEFAULT_REGION`
   - Queue: `SQS_QUEUE`

### Current Queue Usage

#### ✅ Implemented Queue Jobs

1. **SendInAppNotification Job**
   - **Location**: `app/Jobs/SendInAppNotification.php`
   - **Purpose**: Send in-app notifications
   - **Status**: Implements `ShouldQueue`
   - **Usage**: Dispatched for notifications

2. **TriggerWebhookJob**
   - **Location**: `app/Jobs/TriggerWebhookJob.php`
   - **Purpose**: Trigger webhooks
   - **Status**: Implements `ShouldQueue`
   - **Features**:
     - Retry logic (3 tries)
     - Backoff strategy (5, 10, 15 seconds)
     - Failed job handling
     - Webhook call logging

3. **Mailer Mailable**
   - **Location**: `app/Mail/Mailer.php`
   - **Purpose**: Send emails
   - **Status**: Implements `ShouldQueue`
   - **Usage**: All emails via Mailer class are queued

#### ⚠️ Partial Queue Usage

1. **Email Sending**
   - ✅ Emails via Mailer class are queued
   - ⚠️ Some emails may use direct Mail facade
   - ⚠️ Not all emails use queue consistently

2. **Notification Sending**
   - ✅ Some notifications are queued
   - ⚠️ Some notifications may be sent synchronously
   - ⚠️ InAppNotificationService may send synchronously

3. **Job Batching**
   - ✅ Used in some places (EcommercePaymentRepository)
   - ⚠️ Not used consistently
   - ⚠️ Could be used more extensively

#### ❌ Missing Queue Opportunities

1. **File Processing**
   - No queued file processing
   - No queued image processing
   - No queued document processing

2. **Export Operations**
   - Excel exports may run synchronously
   - No queued export generation
   - No queued report generation

3. **Data Processing**
   - Transaction history evaluation may run synchronously
   - Credit score calculation may run synchronously
   - No queued data aggregation

4. **External API Calls**
   - Payment gateway calls may be synchronous
   - Bank verification may be synchronous
   - No queued external API calls

### Queue Workers Configuration

#### Docker Configuration
**Location**: `docker/laravel-worker.conf`

**Configured Workers**:
1. **Default Queue Worker**
   - Queue: `default`
   - Processes: 2
   - Sleep: 3 seconds
   - Tries: 3
   - Timeout: 90 seconds

2. **High Priority Queue Worker**
   - Queue: `high`
   - Processes: 1
   - Sleep: 1 second
   - Tries: 3
   - Timeout: 90 seconds

3. **Low Priority Queue Worker**
   - Queue: `low`
   - Processes: 1
   - Sleep: 5 seconds
   - Tries: 3
   - Timeout: 90 seconds

**Note**: Workers are configured for Redis, but default queue is database

#### Supervisord Configuration
**Location**: `docker/supervisord.conf`

- Manages Laravel queue workers
- Auto-restart on failure
- Logs to `storage/logs/worker-*.log`

### Queue Implementation Recommendations

#### 1. Switch to Redis Queue

**Benefits**:
- Better performance
- Support for priority queues
- Better scalability
- Real-time queue monitoring

**Configuration**:
```env
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=default
```

#### 2. Implement Queue Priorities

**Current Status**: Workers configured but not used

**Recommendations**:
- Use `high` queue for critical jobs
- Use `default` queue for normal jobs
- Use `low` queue for background tasks

**Example**:
```php
// High priority
dispatch(new ProcessPayment($order))->onQueue('high');

// Normal priority
dispatch(new SendEmail($user))->onQueue('default');

// Low priority
dispatch(new GenerateReport($data))->onQueue('low');
```

#### 3. Queue All Background Operations

**Recommendations**:
- Queue all email sending
- Queue file processing
- Queue export operations
- Queue data processing
- Queue external API calls

**Example**:
```php
// Queue file processing
ProcessFileJob::dispatch($file)->onQueue('default');

// Queue export
ExportDataJob::dispatch($filters)->onQueue('low');

// Queue external API call
VerifyBankAccountJob::dispatch($account)->onQueue('default');
```

#### 4. Implement Job Batching

**Current Usage**: Used in some places

**Recommendations**:
- Use job batching for related operations
- Batch email sending
- Batch notification sending
- Batch data processing

**Example**:
```php
Bus::batch([
    new SendEmail($user1),
    new SendEmail($user2),
    new SendEmail($user3),
])
->name('Send Welcome Emails')
->allowFailures()
->dispatch();
```

#### 5. Implement Failed Job Handling

**Current Status**: Basic failed job handling exists

**Recommendations**:
- Implement failed job notifications
- Add retry logic with exponential backoff
- Log failed jobs
- Create failed job dashboard

#### 6. Queue Monitoring

**Recommendations**:
- Monitor queue depth
- Track job processing times
- Monitor failed jobs
- Set up alerts for queue issues

**Tools**:
- Laravel Horizon (for Redis queues)
- Custom dashboard
- Monitoring tools

### Console Commands for Queues

#### Existing Commands
- `php artisan queue:work` - Process jobs
- `php artisan queue:listen` - Listen for jobs
- `php artisan queue:restart` - Restart workers
- `php artisan queue:failed` - List failed jobs
- `php artisan queue:retry` - Retry failed jobs

#### Scheduled Jobs
**Location**: `routes/console.php`

Existing scheduled commands:
- `ProcessRepayments` - Process loan repayments
- `SendRepaymentReminders` - Send repayment reminders
- `CancelUnapprovedLoans` - Cancel unapproved loans
- `MarkExpiredDiscountStatus` - Mark expired discounts
- `VerifyPaystackTransactions` - Verify Paystack transactions

## Caching & Queueing Best Practices

### Caching Best Practices

1. **Cache Strategy**
   - Cache frequently accessed data
   - Use appropriate TTL values
   - Implement cache invalidation
   - Use cache tags for related data

2. **Cache Keys**
   - Use descriptive cache keys
   - Include relevant identifiers
   - Use consistent naming conventions
   - Consider cache key prefixes

3. **Cache Invalidation**
   - Invalidate on data updates
   - Use cache tags for bulk invalidation
   - Implement cache warming
   - Monitor cache hit rates

4. **Cache Performance**
   - Use Redis for better performance
   - Monitor cache memory usage
   - Implement cache compression
   - Use cache compression for large data

### Queueing Best Practices

1. **Job Design**
   - Keep jobs focused and small
   - Avoid long-running jobs
   - Use job batching for related operations
   - Implement proper error handling

2. **Queue Management**
   - Use appropriate queue priorities
   - Monitor queue depth
   - Set appropriate timeouts
   - Implement retry logic

3. **Job Monitoring**
   - Track job processing times
   - Monitor failed jobs
   - Set up alerts for queue issues
   - Log job execution

4. **Scalability**
   - Use Redis for better scalability
   - Scale workers based on load
   - Implement job prioritization
   - Use multiple queue connections

## Implementation Checklist

### Caching
- [ ] Switch to Redis cache
- [ ] Implement query result caching
- [ ] Implement API response caching
- [ ] Implement model caching
- [ ] Add cache invalidation strategies
- [ ] Enable settings caching
- [ ] Monitor cache performance

### Queueing
- [ ] Switch to Redis queue
- [ ] Queue all email sending
- [ ] Queue file processing
- [ ] Queue export operations
- [ ] Queue external API calls
- [ ] Implement queue priorities
- [ ] Set up queue monitoring
- [ ] Implement failed job handling
- [ ] Use job batching consistently

---

**Last Updated**: Based on codebase analysis
**Caching Status**: Limited Implementation - Needs Expansion
**Queueing Status**: Partial Implementation - Needs Standardization













