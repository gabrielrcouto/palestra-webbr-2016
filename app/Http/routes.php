<?php

$app->get('/', function () use ($app) {
    $md5 = md5(Session::getId());

    if (! Session::has('mode')) {
        Session::put('mode', 'participant');
    }

    if (! Session::has('nickname')) {
        $faker = Faker\Factory::create('pt_BR');

        do {
            $nickname = $faker->userName;
        } while (\Cache::has($nickname));

        \Cache::forever($nickname, '1');
        Session::put('nickname', $nickname);
    }

    $mode = Session::get('mode');

    if (! \Cache::has($md5)) {
        \Cache::forever($md5, [
            'mode' => $mode,
            'nickname' => Session::get('nickname'),
        ]);
    }

    //Caso seja rodado no localhost:8000, por exemplo
    //retira os : do nome do servidor
    $host = $_SERVER['HTTP_HOST'];
    $hostExplode = explode(':', $host);
    $host = $hostExplode[0];

    $data = [
        'host' => $_SERVER['HTTP_HOST'],
        'mode' => $mode,
        'websocketsAddress' => $host . '/ws/?session=' . $md5
    ];

    return view('home', $data);
});

$app->get('/presenter', function () use ($app) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Presenter Mode"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Você não é o apresentador?';
        exit;
    } else {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        //O usuário é 'admin' e a senha é 'phprules'?
        if ('admin' === $username && '27da247ac3132070bfe88338846955adf41955fc' === sha1($password)) {
            $md5 = md5(Session::getId());

            //Grava na sessão que o visitante é o apresentador
            Session::put('mode', 'presenter');

            if (\Cache::has($md5)) {
                $cache = \Cache::get('$md5');
                $cache['mode'] = 'presenter';
                $cache['nickname'] = 'Bob';

                \Cache::forever($md5, $cache);
            }

            //Redireciona de volta para a apresentação
            return redirect('/');
        } else {
            header('HTTP/1.0 401 Unauthorized');
            echo 'Aqui não tem biscoito!';
            exit;
        }
    }
});
