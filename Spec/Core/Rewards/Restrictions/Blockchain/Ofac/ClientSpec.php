<?php

namespace Spec\Minds\Core\Rewards\Restrictions\Blockchain\Ofac;

use PhpSpec\ObjectBehavior;
use Minds\Core\Rewards\Restrictions\Blockchain\Ofac\Client;
use \GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Stream;

class ClientSpec extends ObjectBehavior
{
    /** @var GuzzleClient */
    private $guzzleClient;

    public function let(
        GuzzleClient $guzzleClient,
    ) {
        $this->guzzleClient = $guzzleClient;
        $this->beConstructedWith($guzzleClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_get_single_address_from_xml(
        ServerRequestInterface $request,
        Stream $stream,
    ) {
        $xmlResponse = '<?xml version="1.0" standalone="yes"?>
            <sdnList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://tempuri.org/sdnList.xsd">
                <sdnEntry>
                    <uid>1</uid>
                    <idList>
                        <id>
                            <uid>1</uid>
                            <idType>Digital Currency Address - ETH</idType>
                            <idNumber>0x00</idNumber>
                        </id>
                    </idList>
                </sdnEntry>
            </sdnList>';

                
        $request->getBody()
            ->shouldBeCalled()
            ->willReturn($stream);

        $stream->getContents()
            ->shouldBeCalled()
            ->willReturn($xmlResponse);

        $this->guzzleClient->request('GET', 'https://www.treasury.gov/ofac/downloads/sdn.xml', [
            'headers' => [
                'Accept' => 'application/xml',
            ]
        ])
            ->shouldBeCalled()
            ->willReturn($request);

        $this->getAll()->shouldBe([
            [
                'network' => "ETH",
                'address' => "0x00",
            ],
        ]);
    }

    public function it_should_get_multiple_address_for_different_entries_from_xml(
        ServerRequestInterface $request,
        Stream $stream,
    ) {
        $xmlResponse = '<?xml version="1.0" standalone="yes"?>
            <sdnList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://tempuri.org/sdnList.xsd">
                <sdnEntry>
                    <uid>1</uid>
                    <idList>
                        <id>
                            <uid>1</uid>
                            <idType>Digital Currency Address - ETH</idType>
                            <idNumber>0x00</idNumber>
                        </id>
                    </idList>
                </sdnEntry>
                <sdnEntry>
                    <uid>36</uid>
                    <idList>
                        <id>
                            <uid>2</uid>
                            <idType>Digital Currency Address - ETH</idType>
                            <idNumber>0x01</idNumber>
                        </id>
                    </idList>
                </sdnEntry>
                <sdnEntry>
                    <uid>36</uid>
                    <idList>
                        <id>
                            <uid>3</uid>
                            <idType>Digital Currency Address - ETH</idType>
                            <idNumber>0x02</idNumber>
                        </id>
                    </idList>
                </sdnEntry>
                <sdnEntry>
                    <uid>36</uid>
                    <idList>
                        <id>
                            <uid>4</uid>
                            <idType>Digital Currency Address - XBT</idType>
                            <idNumber>1p11</idNumber>
                        </id>
                    </idList>
                </sdnEntry>
            </sdnList>';

                
        $request->getBody()
            ->shouldBeCalled()
            ->willReturn($stream);

        $stream->getContents()
            ->shouldBeCalled()
            ->willReturn($xmlResponse);

        $this->guzzleClient->request('GET', 'https://www.treasury.gov/ofac/downloads/sdn.xml', [
            'headers' => [
                'Accept' => 'application/xml',
            ]
        ])
            ->shouldBeCalled()
            ->willReturn($request);

        $this->getAll()->shouldBe([
            [
                'network' => "ETH",
                'address' => "0x00",
            ],
            [
                'network' => "ETH",
                'address' => "0x01",
            ],
            [
                'network' => "ETH",
                'address' => "0x02",
            ],
            [
                'network' => "XBT",
                'address' => "1p11",
            ]
        ]);
    }

    public function it_should_get_multiple_address_for_same_entry_from_xml(
        ServerRequestInterface $request,
        Stream $stream,
    ) {
        $xmlResponse = '<?xml version="1.0" standalone="yes"?>
            <sdnList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://tempuri.org/sdnList.xsd">
                <sdnEntry>
                    <uid>1</uid>
                    <idList>
                        <id>
                            <uid>1</uid>
                            <idType>Digital Currency Address - ETH</idType>
                            <idNumber>0x00</idNumber>
                        </id>
                        <id>
                            <uid>2</uid>
                            <idType>Digital Currency Address - ETH</idType>
                            <idNumber>0x01</idNumber>
                        </id>
                    </idList>
                </sdnEntry>
            </sdnList>';

                
        $request->getBody()
            ->shouldBeCalled()
            ->willReturn($stream);

        $stream->getContents()
            ->shouldBeCalled()
            ->willReturn($xmlResponse);

        $this->guzzleClient->request('GET', 'https://www.treasury.gov/ofac/downloads/sdn.xml', [
            'headers' => [
                'Accept' => 'application/xml',
            ]
        ])
            ->shouldBeCalled()
            ->willReturn($request);

        $this->getAll()->shouldBe([
            [
                'network' => "ETH",
                'address' => "0x00",
            ],
            [
                'network' => "ETH",
                'address' => "0x01",
            ]
        ]);
    }
}
