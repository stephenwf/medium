<?php

namespace eLife\Medium\Response;

use DateTime;
use DateTimeImmutable;
use JMS\Serializer\Annotation\Type;

final class MediumArticleResponse
{
    /**
     * @Type("string")
     */
    public $uri;

    /**
     * @Type("string")
     */
    public $title;

    /**
     * @Type("DateTime<'Y-m-d\TH:i:s\Z'>")
     */
    public $published;

    /**
     * @Type("string")
     */
    public $impactStatement;

    // needs to be updated for IIIF
    ///**
    // * @Type("eLife\Medium\Response\ImageResponse")
    // */
    //public $image;

    /**
     * @SuppressWarnings(PHPMD.ForbiddenDateTime)
     */
    public function __construct(
        string $uri,
        string $title,
        DateTimeImmutable $published,
        ImageResponse $image = null,
        string $impactStatement = null
    ) {
        $this->uri = $uri;
        $this->title = $title;
        $this->published = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $published->format('Y-m-d\TH:i:s\Z'), $published->getTimezone());
        $this->impactStatement = $impactStatement;
        //$this->image = $image;
    }
}
