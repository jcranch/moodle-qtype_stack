# Sheffield modifications to Stack

## History and background

In Sheffield we used AiM for many years.  This is a system of a similar 
nature and purpose as Stack, but it stands alone rather than being embedded
in a VLE like Moodle, and it uses Maple rather than Maxima as the CAS engine.
In summer 2020 we decided to switch over to using Stack, and we ported a 
large body of questions.  To streamline this we wrote Maxima code to add
various bits of AiM functionality to Stack.  We also designed a new markup
language for Stack questions, similar to the language used for AiM.  We 
translated AiM questions by hand to this new language, and wrote code to
convert the new language to Moodle XML.

## Markup language

Here is a representative sample of the markup language that we are using.
The code to translate to Moodle XML is not currently on GitHub, but will
be placed there at some point.  (There are also various CLI scripts for
handling large numbers of Stack courses and quizzes and questions, and 
these are currently mixed up with the code for the markup language.  This
will require a bit of reorganisation.)
```
name> diff_poly_power
description>
 Find the derivative of \((x^n+1)^m\) or \((x^n+1)^{1/m}\)
 (where n and m are random integers)
h> n : rand_with_step(3,7,1);
   m : rand_with_step(5,10,1) ^ rand_with_prohib(-1,1,[0]);
   yx : (x^n + 1)^m;
   dydx : n * m * x^(n-1) * (x^n + 1)^(m - 1);
   Q : 'diff(y = yx,x) = dydx;
note> {#Q#}
t> Find the derivative of the function \(y = {@yx@}\) with respect to \(x\).<br/>
   \(\displaystyle\frac{dy}{dx}=\){?ans=dydx?}
forbid> diff
mb>
h> simp: true;
   [sc,fb,an] : diff_problem_test(ans,Q);
if> sc = 1
_score> 1
_answernote> correct
else>
_score> 0
_answernote> {#an#}
_feedback> {@fb@}
me>
sb>
t> We use the chain rule.  Put \(u=x^{@n@} + 1\), so \(y = u^{@m@}\).
   Then
   \[ \frac{du}{dx} = {@n * x^(n - 1)@} \]
   \[ \frac{dy}{du} = {@m * u^(m - 1)@} = {@m * (x^n + 1)^(m - 1)@} \]
   so
   \[ \frac{dy}{dx} = \frac{dy}{du} \frac{du}{dx} = {@dydx@}. \]
se>
end>
```
## Branches

In this repository, the `master` branch is the same as the main 
Stack repository.  There are a number of feature branches as listed
below, and there is `sheffield` branch that merges all the feature
branches.  (There are also a number of similar things that are 
still waiting to be ported from AiM; we will deal with these when
we need them, later this semester or over the Christmas break.)

Some of the feature branches could be considered for 
incorporation in the main Stack repository, although additional 
work will be needed to prepare for this in almost all cases.  In 
particular:
* Strings are currently not internationalised.  This is not a 
  trivial issue, because we have a lot of code that generates 
  complex feedback strings whose structure is dependent on 
  English grammar.
* There are currently no unit tests.
* There are various things that follow AiM idioms but should be
  refactored to follow Stack conventions more closely.

### styles:
 This adds miscellaneous useful styles to styles.css

### jsxgraphlocal:
 This adds a file `jsxgraphlocal.js` containing various functions 
 related to JSXGraph that we use for particular question types.
 In particular, it adds functions to generate pictures of maps 
 between finite sets, for use in questions about injectivity and
 surjectivity.  It also adds a hook in `jsxgraph.block.php` to load 
 the file `jsxgraphlocal.js`.

### babylon:
 Adds a Babylon block to CasText, allowing for inclusion of 3d graphics
 using the Babylon library.  Everything is modelled on the JSXGraph block,
 but is simpler as we do not try to take input from the 3d diagram.

 Note that `amd/src/babylon.js`, which is distributed by the Babylon project,
 is a webpack universal module definition.  When interpreted by a Javascript
 engine this will function as an AMD module.  However, Moodle does some
 syntactic manipulation of AMD modules outside of the Javascript engine,
 and this does not work correctly with UMD modules.  Thus, the file
 `amd/build/babylon.min.js` is a copy of the official `babylon.min.js` in which
 the first few lines have been modified to convert it to a standard AMD module.
 However, `babylon.gui.js` is already an AMD module and so did not need this
 transformation.

 As the Babylon library is large, it should really be lazy-loaded.  However,
 for some reason I was not able to make that work correctly.

 In Sheffield we are not actually using the Babylon block, but are using the 
 owl block instead, as described below.

### owl:
 Babylon is a very large and professional library but it is mostly designed
 for games and similar applications, and so does not have a very convenient 
 API for mathematical diagrams.  The owl library is devloped by Neil 
 Strickland.  It has some functions for 2D graphics and some functions for
 3D graphics.  The 3D part is built on top of Babylon and makes it easier
 to use for mathematics.  It is not very professional or feature complete,
 and just offers functions that I happen to have found useful for various
 examples that I have developed.

 This branch adds an owl block to CasText. 

### stackmaxima_local:
 This branch adds a line `load("local.mac")` to `stackmaxima.mac`, and adds a 
 file `local.mac` that just contains a comment.  The branches listed below 
 add various other files of maxima code, and put lines in `local.mac` to 
 read them in.

### builder:
  This branch creates a new input type which allows students to construct 
  an answer by stringing together phrases taken from a prespecified pool.
  The main idea is for students to construct proofs this way.  For this to
  work well, we will need a library of functions to detect (with help from 
  the author) the common ways in which a proof can be wrong, and supply 
  appropriate feedback.  We have some Maple code for this but it is not 
  especially well-developed and in any case has not been ported to 
  Maxima yet.

### diff:
  This branch defines functions for marking differentiation questions.
  It tries to provide tailored feedback for a range of common mistakes.
  It is not currently integrated with the standard Stack framework of
  answer tests.  That should probably be done, but there are some 
  decisions to make about the details.

### extra_inequalities:
  This branch adds functions for dealing with inequalities.  It (and some
  Stack questions using it) were written before I had a good understanding
  of the existing codebase.  All this should now be refactored.

### finmap:
  This branch adds functions for dealing with maps between finite sets, 
  particularly aimed at questions that teach students about injectivity
  and surjectivity.  Output of these functions is consumed by the 
  functions in `jsxgraphlocal.js`.

### functions:
  This branch has code for questions about properties functions of one 
  real variable, especially functions that ask students to identify a
  function given its graph.  This code is translated from Maple, but 
  does not work as well because Maple is better than Maxima at 
  deciding quickly and automatically whether a given function has a
  particular property.  Thus, this branch should probably not be 
  considered stable or mature.

### inv_trig_tex:
  This tells Stack not to use notation like f^{-1}(x) or f^{2}(x).

### mcq:
  This branch contains various code for dealing with multiple choice 
  and multiple response questions, especially multiple response 
  questions in which the options are supplied with explanations
  of why they are correct or incorrect.  There is extensive 
  explanation in the file `stack/maxima/mcq.mac`.

### partial_fractions:
  This branch contains code for questions in which students are asked
  to work with the partial fraction form of rational functions.
  At the moment it mostly expects to factor all polynomials completely 
  over the complex numbers.  There are some functions that allow for
  irreducible real quadratic factors, and we plan to extend the code 
  to deal more systematically with that form.

### stack_misc:
  This adds the file `stack/maxima/stack_misc.mac`, which contains a
  variety of functions that we have found useful.

### svg:
  This adds some functions for generating SVG code in Maxima.  Although
  we have some questions that use this, it would probably be better to
  refactor them to use JSXGraph instead.

### two_d_critical:
  This adds some functions for questions in which students are asked to
  find and classify critical points of functions of two variables.
 