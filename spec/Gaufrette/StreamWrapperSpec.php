<?php

namespace spec\Gaufrette;

use Gaufrette\FilesystemMap;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StreamWrapperSpec extends ObjectBehavior
{
    function let(FilesystemMap $map, Filesystem $filesystem, Stream $stream)
    {
        $filesystem->createStream('filename')->willReturn($stream);
        $map->get('some')->willReturn($filesystem);
        $this->setFilesystemMap($map);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\StreamWrapper');
    }

    function it_opens_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);

        $this->stream_open('gaufrette://some/filename', 'r+')->shouldReturn(true);
    }

    function it_does_not_open_stream_when_key_is_not_defined()
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('The specified path (gaufrette://some) is invalid.'))
            ->duringStream_open('gaufrette://some', 'r+');
    }

    function it_does_not_open_stream_when_host_is_not_defined()
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('The specified path (gaufrette:///somefile) is invalid.'))
            ->duringStream_open('gaufrette:///somefile', 'r+')
        ;
    }

    function it_does_not_read_from_stream_when_is_not_opened()
    {
        $this->stream_read(10)->shouldReturn(false);
    }

    function it_does_not_read_from_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->read(4)->willReturn('some');

        $this->stream_open('gaufrette://some/filename', 'r+');
        $this->stream_read(4)->shouldReturn('some');
    }

    function it_does_not_write_to_stream_when_is_not_opened()
    {
        $this->stream_write('some content')->shouldReturn(0);
    }

    function it_writes_to_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->write('some content')->shouldBeCalled()->willReturn(12);

        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_write('some content')->shouldReturn(12);
    }

    function it_does_not_close_stream_when_is_not_opened($stream)
    {
        $stream->close()->shouldNotBeCalled();
        $this->stream_close();
    }

    function it_closes_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->close()->shouldBeCalled();
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_close();
    }

    function it_does_not_flush_stream_when_is_not_opened(Stream $stream)
    {
        $stream->flush()->shouldNotBeCalled();
        $this->stream_flush();
    }

    function it_flushes_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->flush()->shouldBeCalled();
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_flush();
    }

    function it_does_not_seek_in_stream_when_is_not_opened(Stream $stream)
    {
        $stream->seek(12, SEEK_SET)->shouldNotBeCalled();
        $this->stream_seek(12, SEEK_SET);
    }

    function it_seeks_in_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->seek(12, SEEK_SET)->shouldBeCalled()->willReturn(true);
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_seek(12, SEEK_SET)->shouldReturn(true);
    }

    function it_does_not_tell_about_position_in_stream_when_is_not_opened(Stream $stream)
    {
        $stream->tell()->shouldNotBeCalled();
        $this->stream_tell();
    }

    function it_does_tell_about_position_in_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->tell()->shouldBeCalled()->willReturn(12);
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_tell()->shouldReturn(12);
    }

    function it_does_not_mark_as_eof_if_stream_is_not_opened(Stream $stream)
    {
        $stream->eof()->shouldNotBeCalled();
        $this->stream_eof();
    }

    function it_checks_if_eof(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $this->stream_open('gaufrette://some/filename', 'w+');
        $stream->eof()->willReturn(false);

        $this->stream_eof()->shouldReturn(false);

        $stream->eof()->willReturn(true);
        $this->stream_eof()->shouldReturn(true);
    }

    function it_does_not_get_stat_when_is_not_open()
    {
        $this->stream_stat()->shouldReturn(false);
    }

    function it_stats_file(Stream $stream)
    {
        $stat = [
            'dev' => 1,
            'ino' => 12,
            'mode' => 0777,
            'nlink' => 0,
            'uid' => 123,
            'gid' => 1,
            'rdev' => 0,
            'size' => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks' => 1,
        ];
        $stream->open(Argument::any())->willReturn(true);
        $stream->stat()->willReturn($stat);

        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_stat()->shouldReturn($stat);
    }

    function it_should_stat_from_url(Stream $stream)
    {
        $stat = [
            'dev' => 1,
            'ino' => 12,
            'mode' => 0777,
            'nlink' => 0,
            'uid' => 123,
            'gid' => 1,
            'rdev' => 0,
            'size' => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks' => 1,
        ];
        $stream->open(Argument::any())->willReturn(true);
        $stream->stat()->willReturn($stat);

        $this->url_stat('gaufrette://some/filename', STREAM_URL_STAT_LINK)->shouldReturn($stat);
    }

    function it_stats_even_if_it_cannot_be_open(Filesystem $filesystem, Stream $stream)
    {
        $filesystem->createStream('dir/')->willReturn($stream);
        $stream->open(Argument::any())->willThrow(new \RuntimeException);
        $stream->stat(Argument::any())->willReturn(['mode' => 16893]);
        $this->url_stat('gaufrette://some/dir/', STREAM_URL_STAT_LINK)->shouldReturn(['mode' => 16893]);
    }

    function it_does_not_unlink_when_cannot_open(Stream $stream)
    {
        $stream->open(Argument::any())->willThrow(new \RuntimeException);
        $this->unlink('gaufrette://some/filename')->shouldReturn(false);
    }

    function it_unlinks_file(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->unlink()->willReturn(true);

        $this->unlink('gaufrette://some/filename')->shouldReturn(true);
    }

    function it_does_not_cast_stream_if_is_not_opened()
    {
        $this->stream_cast(STREAM_CAST_FOR_SELECT)->shouldReturn(false);
    }

    function it_casts_stream(Stream $stream)
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->cast(STREAM_CAST_FOR_SELECT)->willReturn('resource');

        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_cast(STREAM_CAST_FOR_SELECT)->shouldReturn('resource');
    }
}
