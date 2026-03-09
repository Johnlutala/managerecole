<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class MerchantConfigPayload
{
    #[Assert\NotBlank(message: "Code marchand obligatoire")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9]+$/",
        message: "Le code marchand doit être alphanumérique"
    )]
    private string $shortcode;


    public function __construct(
        string $shortcode_,
    ) {
        $this->shortcode = $shortcode_;
    }

}