<?php

namespace Apility\Payment\View\Components;

use chillerlan\QRCode\QRCode as QrCodeGenerator;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

class QrCode extends Component
{
    public ?string $string;

    public function __construct(?string $string = null)
    {
        $this->string = $string;
    }

    public function qr(): HtmlString
    {
        $qr = new QrCodeGenerator();
        return new HtmlString($qr->render($this->string));
    }

    public function shouldRender()
    {
        return $this->string !== null;
    }

    public function render()
    {
        dd($this->qr);
        return view('payment::components.qr-code', [
            'qr' => $this->qr()
        ]);
    }
}
