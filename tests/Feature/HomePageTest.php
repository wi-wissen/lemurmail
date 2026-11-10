<?php

it('explains Lemur Mail and offers one way for students and one for teachers', function () {
    $this->get(route('home'))->assertOk()
        ->assertSee(__('home.title'))
        ->assertSee(__('home.student_title'))
        ->assertSee(__('home.teacher_title'))
        ->assertSee(route('mailbox.login.form'), false)
        ->assertSee(route('login'), false)
        ->assertSee(route('register'), false)
        ->assertDontSee('name="pin"', false);
});

it('links to the about page only when it is configured', function () {
    config(['lemurmail.about_url' => 'https://about.example.org/']);
    $this->get(route('home'))->assertSee('https://about.example.org/', false);

    config(['lemurmail.about_url' => '']);
    $this->get(route('home'))->assertDontSee(__('home.about'));
});

it('marks every sign-in page with its audience and links to the other login', function (string $route, string $role, ?string $other) {
    $response = $this->get(route($route))->assertOk()
        ->assertSee(__('common.role_'.$role))
        ->assertSee(__('common.to_home'));

    if ($other !== null) {
        $response->assertSee(__('common.switch_to_'.$other));
    }
})->with([
    ['mailbox.login.form', 'student', 'teacher'],
    ['login', 'teacher', 'student'],
    ['register', 'teacher', 'student'],
    ['password.request', 'teacher', 'student'],
]);

it('shows the start page and the sign-in pages in English', function () {
    $this->withHeader('Accept-Language', 'en')->get(route('home'))->assertSee('What would you like to do?');
    $this->withHeader('Accept-Language', 'en')->get(route('mailbox.login.form'))->assertSee('For students');
});
