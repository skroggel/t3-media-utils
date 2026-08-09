=======
Testing
=======

Unit tests
==========

Run all unit tests:

..  code-block:: bash

    ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.xml

Run a single unit test file:

..  code-block:: bash

    ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.xml \
        vendor/madj2k/t3-media-utils/Tests/Unit/Whatever/Whatever.php

Run a single unit test method:

..  code-block:: bash

    ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.xml \
        vendor/madj2k/t3-media-utils/Tests/Unit/Whatever/Whatever.php \
        --filter MethodName

Functional tests
================

Run all functional tests:

..  code-block:: bash

    ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.functional.xml

Run a single functional test file:

..  code-block:: bash

    ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.functional.xml \
        vendor/madj2k/t3-media-utils/Tests/Functional/Whatever/Whatever.php

Run a single functional test method:

..  code-block:: bash

    ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.functional.xml \
        vendor/madj2k/t3-media-utils/Tests/Functional/Whatever/Whatever.php \
        --filter MethodName
