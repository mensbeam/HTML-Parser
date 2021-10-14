HTML DOM serialization tests
============================

The format of these tests is essentially the format of html5lib's tree construction tests in reverse. There are, however, important differences, so the format is documented in full here.

Each file containing tree construction tests consists of any number of
tests separated by two newlines (LF) and a single newline before the end
of the file. For instance:

    [TEST]LF
    LF
    [TEST]LF
    LF
    [TEST]LF

Where [TEST] is the following format:

Each test begins with a line reading "#document" or "#fragment"; subsequent
lines represent the document or document fragment (respectively) used as
input, until a line is encountered which reads "#output", "#script-on",
or "#script-off".


