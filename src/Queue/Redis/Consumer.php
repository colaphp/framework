<?php

namespace Cola\Queue\Redis;

interface Consumer
{
    public function consume($data);
}