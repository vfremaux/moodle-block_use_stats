####################################
#
#    Moodle Third Party Adds-on
#
####################################
#
# module: Use Stats Block
# type: block
# whouses: teachers, eventually students
# developer: Valery Fremaux (valery.Fremaux@club-internet.fr)
# date: 2012/03/18
# Version : Moodle 2

This block allows displaying use stats for the current user.

Use stats are given as an answer to the "how many time I spent on this Moodle". This may be usefull in situation where work time should be known to estimate average personnal productivity and enhancing its own process. 

In other situations could it be used as an objetive proof of real work in some extreme and conflictual situations we hope they never occur.

## Short overview ##

This block samples the user's log records and thresholds the activity backtrace. The main hypothesis is that any activity type unless offline activity or in-classroom activity may underlie a constant loggin track generation. 

The block compiles all log events and summarizes all intervals larger than an adjustable threshold. Compilation are also made on a course basis.

The more Moodle is used as a daily content editing tool, the more accurate should be this report.

## Install the block ##

unzip this distribution in the <MOODLE_INSTALL>/blocks folder

browse to the "administration" page to make the block registered by Moodle.

## Use the block ##

Add a block in any workspace you use. Compilation will be visible to the current user, with no restrictions if he is a teacher. 

Students may be given access to their own report, if the instance is programmed for by the teacher within a course context, or by the administrator out of a course context (MyMoodle, general pages)

## Language files ##

For Moodle < 1.7.0, copy the lang directory in the adequate location.