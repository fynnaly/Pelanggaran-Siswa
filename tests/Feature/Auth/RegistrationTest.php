<?php

it('has no public registration route', function () {
    $this->get('/register')->assertNotFound();
});
