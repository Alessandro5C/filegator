<?php

/*
 * This file is part of the FileGator package.
 *
 * (c) Milos Stojanovic <alcalbg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Filegator\Controllers;

use Filegator\Kernel\Request;
use Filegator\Kernel\Response;
use Filegator\Services\Auth\AuthInterface;
use Filegator\Services\Logger\LoggerInterface;
use Rakit\Validation\Validator;

class AuthController
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function login(Request $request, Response $response, AuthInterface $auth)
    {

        $username = $request->input('username');
        $password = $request->input('password');

        // echo 'USER ' . $username . ' & PWD ' . $password;

        if ($auth->authenticate($username, $password)) {
            $this->logger->log("Logged in {$username} from IP ".$request->getClientIp());

            return $response->json($auth->user());
        }

        $this->logger->log("Login failed for {$username} from IP ".$request->getClientIp());

        return $response->json('Login failed, please try again', 422);
    }

    public function autologin(Request $request, Response $response, AuthInterface $auth)
    {
        // try {
        //     // $file = $this->storage->readStream((string) base64_decode($request->input('path')));
        //     $file = $this->storage->readStream((string) $request->input('path'));
        //     $file = $this->storage->readStream((string) $request->input('path'));
        // } catch (\Exception $e) {
        //     return $response->redirect('/');
        // }

        $username = $request->input('user');
        $password = $request->input('pwd');

        if ($auth->authenticate($username, $password)) {
            $this->logger->log("Logged in {$username} from IP ".$request->getClientIp());

            return $response->json($auth->user());
        }

        $this->logger->log("Login failed for {$username} from IP ".$request->getClientIp());

        return $response->json('Login failed, please try again', 422);
    }

    public function logout(Response $response, AuthInterface $auth)
    {
        return $response->json($auth->forget());
    }

    public function getUser(Response $response, AuthInterface $auth)
    {
        $user = $auth->user() ?: $auth->getGuest();

        return $response->json($user);
    }

    public function changePassword(Request $request, Response $response, AuthInterface $auth, Validator $validator)
    {
        $validator->setMessage('required', 'This field is required');
        $validation = $validator->validate($request->all(), [
            'oldpassword' => 'required',
            'newpassword' => 'required',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();

            return $response->json($errors->firstOfAll(), 422);
        }

        if (! $auth->authenticate($auth->user()->getUsername(), $request->input('oldpassword'))) {
            return $response->json(['oldpassword' => 'Wrong password'], 422);
        }

        return $response->json($auth->update($auth->user()->getUsername(), $auth->user(), $request->input('newpassword')));
    }
}
