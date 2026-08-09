=====
Usage with EXT:content_blocks
=====

The TypoScript setup adds the Media Utils partial path to ``lib.contentBlock``.
Custom content elements can therefore render files with the bundled partial:

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, maxWidth: 1000, settings: settings}" />

Default settings can be configured on the content element:

..  code-block:: typoscript

    tt_content.vendor_element {
        settings {
            media {
                image.dimensions.maxWidth = 650
                video {
                    dimensions {
                        width = 1370
                        height = 771
                    }
                    additionalConfig {
                        playsinline = 1
                        autoplay = 1
                        mute = 1
                        loop = 0
                    }
                }
            }
        }
    }


Crop variants per breakpoint
============================

Responsive images can use different TYPO3 crop variants for different
breakpoints. This is useful when the same image should be cropped differently
on desktop, tablet and mobile viewports.

The responsive image configuration is passed through
``settings.txMediaUtils.responsiveImages``. Each entry below ``breakpoints`` can
select a TYPO3 crop variant with the ``cropVariant`` option.

..  code-block:: typoscript

    tt_content.vendor_element {
        settings {
            txMediaUtils.responsiveImages {
                loading = eager
                breakpoints {
                    0 {
                        cropVariant = default
                    }
                    1 {
                        cropVariant = tablet
                    }
                    2 {
                        cropVariant = mobile
                    }
                }
            }
        }
    }

In this example the generated responsive image output uses:

..  list-table::
    :header-rows: 1

    * - Breakpoint key
      - Crop variant
      - Typical use
    * - ``0``
      - ``default``
      - Desktop or default layout.
    * - ``1``
      - ``tablet``
      - Tablet-sized layout.
    * - ``2``
      - ``mobile``
      - Mobile layout.

The crop variant names must exist in the TCA crop configuration of the used
field. Media Utils does not create the crop variants automatically; it reads the
crop information stored on the FAL reference and applies the variant configured
for the current breakpoint.

Call the bundled media partial as usual:

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, maxWidth: 1000, settings: settings}" />

If ``maxWidth`` or ``width`` is passed directly to the partial, that explicit
argument has priority. If no explicit dimension is passed, the partial falls
fall back to dimensions configured in the selected settings group below ``settings.media``.
The responsive image configuration from
``settings.txMediaUtils.responsiveImages`` is applied automatically.

Settings variants
=================

A content element often has more than one visual style, for example a compact
``small`` variant and a full-width ``big`` variant. In this case you can group
media-related settings below sub-keys in ``settings.media`` and select the desired group
with the ``settingsVariant`` argument.

..  code-block:: typoscript

    tt_content.vendor_element {
        settings {
            media {
                small {
                    image {
                        dimensions {
                            maxWidth = 650
                        }
                    }
                    video {
                        dimensions {
                            width = 1370
                            height = 771
                        }
                        additionalConfig {
                            autoplay = 1
                            mute = 1
                            loop = 0
                            modestbranding = 1
                        }
                    }
                }

                big {
                    image {
                        dimensions {
                            maxWidth = 1200
                        }
                    }
                    video {
                        dimensions {
                            width = 1920
                            height = 1080
                        }
                        additionalConfig {
                            autoplay = 1
                            mute = 1
                            loop = 0
                            modestbranding = 1
                        }
                    }
                }
            }
        }
    }

Render the ``small`` variant like this:

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, maxWidth: 1000, settings: settings, settingsVariant: 'small'}" />

Render the ``big`` variant like this:

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, settings: settings, settingsVariant: 'big'}" />

The selected ``settingsVariant`` only changes the selected settings group below
``settings.media``. The responsive image configuration below
``settings.txMediaUtils.responsiveImages`` is still used for breakpoint,
``srcset``, ``sizes``, lazy-loading and crop-variant handling.

Explicit partial arguments
==========================

Values passed directly to the partial have priority over values from the
selected settings variant.

Example:

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, maxWidth: 1000, settings: settings, settingsVariant: 'small'}" />

In this case, the explicit ``maxWidth`` value is used for the rendered image. If
``maxWidth`` or ``width`` is omitted, the partial can use the configured value
from ``settings.media.small.image.dimensions``.


Combining variants and crop variants
====================================

You can combine both concepts: use ``settingsVariant`` for the semantic style of
the content element and use ``breakpoints.*.cropVariant`` for the image crop used
inside the generated responsive image sources.

..  code-block:: typoscript

    tt_content.vendor_element {
        settings {
            media {
                teaser.image.dimensions.maxWidth = 720
                hero.image.dimensions.maxWidth = 1600
            }

            txMediaUtils.responsiveImages {
                loading = lazy
                breakpoints {
                    0 {
                        cropVariant = default
                    }
                    1 {
                        cropVariant = tablet
                    }
                    2 {
                        cropVariant = mobile
                    }
                }
            }
        }
    }

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, settings: settings, settingsVariant: 'hero'}" />

In this example, ``hero`` selects the default dimensions from
``settings.media.hero``. The final image sources still use the configured crop
variants ``default``, ``tablet`` and ``mobile`` for the generated breakpoints.

