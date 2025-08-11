<?php

namespace App\Services;

use App\Models\ChannelCredential;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Exception;

class DynamicChannelCredentialService
{
    protected ?ChannelCredential $credential = null;

    public function __construct(Model $credentialable, string $channel, ?string $provider = null)
    {


    //log variables  passed to the constructor

        // Log::debug('DynamicChannelCredentialService initialized', [
        //     'credentialable' => $credentialable,
        //     'channel' => $channel,
        //     'provider' => $provider,
        // ]);
        // Debug: Log entry into constructor
        Log::debug('Entered DynamicChannelCredentialService::__construct', [
            'channel' => $channel,
            'provider' => $provider,
            'credentialable_class' => get_class($credentialable),
            'credentialable_id' => $credentialable->id ?? null,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);

        // Optional: Xdebug breakpoint
        if (function_exists('xdebug_break')) {
            xdebug_break();
        }

        $modelClass = get_class($credentialable);

        if (!method_exists($credentialable, 'channelCredentials')) {
            Log::error("Model [{$modelClass}] does not have channelCredentials() defined.");
            throw new Exception("The model [{$modelClass}] must implement a channelCredentials() method.");
        }

        $query = $credentialable->channelCredentials()
            ->forChannel($channel)
            ->where('status', 'active');

        if ($provider) {
            $query->forProvider($provider);
        }

        // Debug: Log query before execution
    Log::debug('About to fetch credential', [
            'query' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $this->credential = $query->first();

        if (app()->environment('local') || config('app.debug')) {
            Log::info("Loaded {$channel} credentials", [
                'credential' => $this->credential?->toArray(),
                'for_model' => class_basename($credentialable),
                'model_class' => $modelClass,
                'model_id' => $credentialable->id ?? null
            ]);
        }

        if (!$this->credential) {
            $type = class_basename($credentialable);
            $id = $credentialable->id ?? 'unknown';
            Log::warning("No active {$channel} credentials found for {$type} ID: {$id}");
            throw new Exception("No active {$channel} credentials found for {$type} ID: {$id}");
        }

        // Debug: Log successful credential fetch
        Log::debug('Credential successfully loaded', [
            'credential' => $this->credential->toArray()
        ]);
    }

    public function getCredential(): ChannelCredential
    {
        Log::debug('getCredential called');
        return $this->credential;
    }

    public function getPhoneNumber(): ?string
    {
        Log::debug('getPhoneNumber called');
        return $this->credential->phone_number;
    }

    public function getProvider(): ?string
    {
        Log::debug('getProvider called');
        return $this->credential->provider;
    }

    public function getAccountId(): ?string
    {
        Log::debug('getAccountId called');
        return $this->credential->account_id;
    }

    public function getInstanceId(): ?string
    {
        Log::debug('getInstanceId called');
        return $this->credential->instance_id;
    }

    public function getApiUrl(): ?string
    {
        $apiUrl = $this->credential->api_url;
        Log::debug('getApiUrl called', ['api_url' => $apiUrl]);
        return $apiUrl;
    }

    public function getApiToken(): ?string
    {
        $apiToken = $this->credential->api_token;
        Log::debug('getApiToken called', ['api_token' => $apiToken]);
        return $apiToken;
    }

    public function getApiKey(): ?string
    {
        $apiKey = $this->credential->api_key;
        Log::debug('getApiKey called', ['api_key' => $apiKey]);
        return $apiKey;
    }

    public function getPartnerId(): ?string
    {
        $partnerId = $this->credential->account_sid;
        Log::debug('getPartnerId called', ['account_sid' => $partnerId]);
        return $partnerId;
    }
}
