<?php

declare(strict_types=1);

namespace lsolesen\pel;

/**
 * Class representing Exif data.
 *
 * Exif data resides as {@link PelJpegContent data} and consists of a
 * header followed by a number of {@link PelJpegIfd IFDs}.
 *
 * The interesting method in this class is {@link getTiff()} which
 * will return the {@link PelTiff} object which really holds the data
 * which one normally think of when talking about Exif data. This is
 * because Exif data is stored as an extension of the TIFF file
 * format.
 */
class PelExif extends PelJpegContent implements \Stringable
{
    /**
     * Exif header.
     *
     * The Exif data must start with these six bytes to be considered
     * valid.
     */
    public const EXIF_HEADER = "Exif\0\0";

    /**
     * The PelTiff object contained within.
     */
    private ?PelTiff $tiff = null;

    /**
     * Construct a new Exif object.
     *
     * The new object will be empty --- use the {@link load()} method to
     * load Exif data from a {@link PelDataWindow} object, or use the
     * {@link setTiff()} to change the {@link PelTiff} object, which is
     * the true holder of the Exif {@link PelEntry entries}.
     */
    public function __construct()
    {
        // nothing to be done
    }

    /**
     * Return a string representation of this object.
     *
     * @return string a string describing this object. This is mostly
     *         useful for debugging.
     */
    public function __toString(): string
    {
        return Pel::tra("Dumping Exif data...\n") . $this->tiff?->__toString();
    }

    /**
     * Load and parse Exif data.
     *
     * This will populate the object with Exif data, contained as a
     * {@link PelTiff} object. This TIFF object can be accessed with
     * the {@link getTiff()} method.
     */
    public function load(PelDataWindow $d): void
    {
        Pel::debug('Parsing %d bytes of Exif data...', $d->getSize());

        /* There must be at least 6 bytes for the Exif header. */
        if ($d->getSize() < 6) {
            throw new PelInvalidDataException('Expected at least 6 bytes of Exif data, found just %d bytes.', $d->getSize());
        }

        /* Verify the Exif header */
        if ($d->strcmp(0, self::EXIF_HEADER)) {
            $d->setWindowStart(strlen(self::EXIF_HEADER));
        } else {
            throw new PelInvalidDataException('Exif header not found.');
        }

        /* The rest of the data is TIFF data. */
        $this->tiff = new PelTiff();
        $this->tiff->load($d);
    }

    /**
     * Change the TIFF information.
     *
     * Exif data is really stored as TIFF data, and this method can be
     * used to change this data from one {@link PelTiff} object to
     * another.
     *
     * @param PelTiff $tiff
     *            the new TIFF object.
     */
    public function setTiff(PelTiff $tiff): void
    {
        $this->tiff = $tiff;
    }

    /**
     * Get the underlying TIFF object.
     *
     * The actual Exif data is stored in a {@link PelTiff} object, and
     * this method provides access to it.
     *
     * @return PelTiff|null the TIFF object with the Exif data.
     */
    public function getTiff(): ?PelTiff
    {
        return $this->tiff;
    }

    /**
     * Produce bytes for the Exif data.
     *
     * @return string bytes representing this object.
     */
    public function getBytes(): string
    {
        return self::EXIF_HEADER . $this->tiff?->getBytes();
    }
}
