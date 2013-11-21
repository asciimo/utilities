#! /usr/bin/env python

import sys
import os
import re
import getopt

""" Globals """
verbose = False
recursive = False
ignore_patterns = []

def main():

    global verbose, recursive, ignore_patterns

    directory = "."

    try:
        opts, args = getopt.getopt(sys.argv[1:], "", ["help", "directory=",
        "ignore=", "verbose", "recursive"])
    except getopt.GetoptError as err:
        # print help information and exit
        print str(err)
        usage()
        sys.exit(2)

    for o, a in opts:
        if o == "--directory":
            directory = a
        elif o == "--recursive":
            recursive = True
        elif o == "--verbose":
            verbose = True
        elif o == "--ignore":
            init_ignore_patterns(a)
        elif o == "--help":
            usage()
            sys.exit(0)

    get_files(directory)


def usage():
    print("usage: cdn_update.py [--directory=<directory>] [--ignore=<filename>] [--recursive] [--help] [--verbose]");

def init_ignore_patterns(filename):
    """Compiles each pattern in filename into a regular expression,
    and stores the results in the global ignore_patterns list."""
    global ignore_patterns
    empty_line = re.compile('^\s*$')
    comment_line = re.compile('^\s*#')
    try:
        with open(filename) as f:
            for line in f:
                if(empty_line.match(line) or comment_line.match(line)):
                    continue
                ignore_patterns.append(re.compile(line))
    except (IOError):
        print "Unable to open %s" % filename
        sys.exit();

def get_files(directory):

    global verbose, recursive, ignore_patterns

    for root, subdirs, files in os.walk(directory):
        for filename in files:
            path = os.path.join(root, filename)
            for pattern in ignore_patterns:
                if pattern.match(path):
                    if verbose:
                        print "Ignoring file: s%" % path
                    continue
            if verbose:
                print "Processing: %s" % path
            replacements = replace(filename)
            if verbose:
                print "Replaced %d instances in %s" % (replacements, path)
        for subdir in subdirs:
            path = os.path.join(root, subdir)
            for pattern in ignore_patterns:
                if pattern.match(path):
                    if verbose:
                        print "Ignoring directory: s%" % path
                    continue
                if verbose:
                    print "Subdirectory: %s" % path
                if recursive:
                    get_files(path)

def replace(filename):
    return 3

if __name__ == "__main__":
    main()
