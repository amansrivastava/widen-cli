<?php

namespace App\Traits;

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
}
