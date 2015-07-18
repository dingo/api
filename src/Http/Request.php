<?php

namespace Dingo\Api\Http;

use Illuminate\Http\Request as IlluminateRequest;
use Dingo\Api\Contract\Http\Request as RequestInterface;

class Request extends IlluminateRequest implements RequestInterface
{
    /**
     * Create a new Dingo request instance from an Illuminate request instance.
     *
     * @param \Illuminate\Http\Request $old
     *
     * @return \Dingo\Api\Http\Request
     */
    public function createFromIlluminate(IlluminateRequest $old)
    {
        $new = new static(
            $old->query->all(), $old->request->all(), $old->attributes->all(),
            $old->cookies->all(), $old->files->all(), $old->server->all(), $old->content
        );

        if ($session = $old->getSession()) {
            $new->setSession($old->getSession());
        }

        $new->setRouteResolver($old->getRouteResolver());

        return $new;
    }
}
