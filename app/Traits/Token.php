<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait Token
{
    protected string $token;

    public function getToken(): string {
        if($this->option('token')) {
            $this->token = $this->option('token');
            $this->info("Token loaded from option");
        } elseif(env('TOKEN')) {
            $this->token = env('TOKEN');
            $this->info("Token loaded from env.");
        } else {
            $this->token = $this->secret('Enter Widen auth token');
        }
        return $this->token;
    }

    public function checkConnection() {
        $headers = [
            'authorization' => 'Bearer ' . $this->token
        ];
        $this->client = Http::widen()->withHeaders($headers);
        $response = $this->client->get('/user');
        if($response->successful()) {
            $this->info("Successfully connected to Widen.");
            return $this->client;
        }
        else {
            $this->error("Failed to connect to Widen.");
        }
    }
}
