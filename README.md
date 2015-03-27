#Drupal to Wordpress Exporter

Work In Progress
=======
This isn't ready for prime-time yet. The code is still messy and gross

What it WILL do
=======
This script will successfully export your Drupal 7 post and page bodies
along with their titles and URL aliases to an HTML table, which you can
then use to import to your SQL by means of phpmyadmin and CSV.

What I'm working on now
=======
I'm working on building an SQL statement builder which will essentially build
you a completely new Wordpress 4 database using the stuff that this script
extracts. You will be able to import that MySQL database, point Wordpress to it
and hit the ground running.

Along the way, I'm going have to figure out how taxonomies should map to
Wordpress categories or tags. Any advice or ideas here would be helpful.
My own use case is my gaming blog. On it, I've got several taxonomies
that map well to categories as well as a few taxonomies that map to tags.

Hmm...