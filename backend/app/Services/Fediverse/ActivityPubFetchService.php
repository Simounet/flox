<?php

declare(strict_types=1);

namespace App\Services\Fediverse;

use ActivityPhp\Type;
use ActivityPhp\Type\AbstractObject;
use ActivityPhp\Type\Ontology;
use ActivityPhp\Type\TypeConfiguration;
use App\Profile;
use App\Services\Fediverse\HttpSignature;
use App\Services\HelpersService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivityPubFetchService
{
    public function get(string $url): AbstractObject
    {
        Log::debug("[ActivityPubFetchService] URL: $url");
        $this->urlValidation($url);

        try {
            $res = Http::withHeaders($this->headers($url))
                ->timeout(30)
                ->connectTimeout(5)
                ->retry(3, 500, function (\Exception $exception) {
                    if($exception->response->status() === 410) {
                        return false;
                    }
                    return $exception instanceof ConnectionException;
                })
                ->get($url);
        } catch (RequestException $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        } catch (ConnectionException $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        if(!$res->ok()) {
            throw new \Exception('Wrong call');
        }
        $remoteProfile = $res->body();
        Log::debug("[ActivityPubFetchService] REMOTE PROFILE " . $remoteProfile);
        TypeConfiguration::set('undefined_properties', 'ignore');
        Ontology::load('*');
        return Type::fromJson($remoteProfile);
    }

    private function headers(string $url): array
    {
        $instanceActor = Profile::select(['key_id_url', 'private_key'])->where('id', Profile::INSTANCE_ACTOR_ID)->first();
        $headers = (new HttpSignature())->instanceActorSign(
            $url,
            $instanceActor->private_key,
            $instanceActor->key_id_url
        );
        return $headers;
    }

    private function urlValidation(string $url): void
    {
        if((new HelpersService())->urlValidate($url) === false) {
            throw new \Exception('Wrong URL');
        }
    }
}
