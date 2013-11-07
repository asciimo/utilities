#! /usr/bin/env python

import sys
import os
import re
import getopt

""" Globals """
verbose = False
recursive = False

def main():

    global verbose, recursive

    directory = "."

    try:
        opts, args = getopt.getopt(sys.argv[1:], "", ["help", "directory=",
        "verbose", "recursive"])
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
        elif o == "--help":
            usage()
            sys.exit(0)

    get_files(directory)


def usage():
    print("usage: cdn_update.py [--directory=<directory>] [--recursive] [--help] [--verbose]");

def get_files(directory):

    global verbose, recursive

    for root, subdirs, files in os.walk(directory):
        if verbose:
            print "Descending into %s" % root
        for filename in files:
            print "Filename: %s" % os.path.join(root, filename)
            replace(filename)
        for subdir in subdirs:
            print "Subdirectory: %s" % os.path.join(root, subdir)
        if recursive == False:
            return

def replace(filename):
    return

if __name__ == "__main__":
    main()
