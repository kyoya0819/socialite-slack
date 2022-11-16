<?php

namespace Mpociot\Socialite\Slack;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SLACK';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://slack.com/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://slack.com/api/oauth.access';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://slack.com/api/users.identity?token='.$token
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): \Laravel\Socialite\Two\User|User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['user']['id'],
            'nickname' => $user['user']['name'],
            'name' => $user['user']['name'],
            'email' => $user['user']['email'],
            'avatar' => $user['user']['image_192'],
        ]);
    }

    /**
     * Get the account ID of the current user.
     *
     * @param string $token
     *
     * @return string
     * @throws GuzzleException
     */
    protected function getUserId(string $token): string
    {
        $response = $this->getHttpClient()->get(
            'https://slack.com/api/auth.test?token='.$token
        );

        $response = json_decode($response->getBody()->getContents(), true);

        return $response['user_id'];
    }

    /**
     * Get the access token for the given code.
     *
     * @param string $code
     *
     * @return string
     * @throws GuzzleException
     */
    public function getAccessToken(string $code): string
    {
        $postKey = (version_compare(ClientInterface::MAJOR_VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return json_decode($response->getBody(), true)['access_token'];
    }
}
