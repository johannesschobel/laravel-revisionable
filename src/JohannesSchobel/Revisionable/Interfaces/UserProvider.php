<?php

namespace JohannesSchobel\Revisionable\Interfaces;

interface UserProvider
{
    /**
     * @return string|null
     */
    public function getUser();

    /**
     * @return integer
     */
    public function getUserId();
}
