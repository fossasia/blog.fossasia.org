/*
 * resizable_methods.js
 */
(function($) {

module("resizable: methods");

test("init", function() {
	expect(6);

	$("<div></div>").appendTo('body').resizable().remove();
	ok(true, '.resizable() called on element');

	$([]).resizable().remove();
	ok(true, '.resizable() called on empty collection');

	$('<div></div>').resizable().remove();
	ok(true, '.resizable() called on disconnected DOMElement');

	$('<div></div>').resizable().resizable("foo").remove();
	ok(true, 'arbitrary method called after init');

	el = $('<div></div>').resizable()
	var foo = el.data("foo.resizable");
	el.remove();
	ok(true, 'arbitrary option getter after init');

	$('<div></div>').resizable().data("foo.resizable", "bar").remove();
	ok(true, 'arbitrary option setter after init');
});

test("destroy", function() {
	expect(6);

	$("<div></div>").appendTo('body').resizable().resizable("destroy").remove();
	ok(true, '.resizable("destroy") called on element');

	$([]).resizable().resizable("destroy").remove();
	ok(true, '.resizable("destroy") called on empty collection');

	$('<div></div>').resizable().resizable("destroy").remove();
	ok(true, '.resizable("destroy") called on disconnected DOMElement');

	$('<div></div>').resizable().resizable("destroy").resizable("foo").remove();
	ok(true, 'arbitrary method called after destroy');

	el = $('<div></div>').resizable();
	var foo = el.resizable("destroy").data("foo.resizable");
	el.remove();
	ok(true, 'arbitrary option getter after destroy');

	$('<div></div>').resizable().resizable("destroy").data("foo.resizable", "bar").remove();
	ok(true, 'arbitrary option setter after destroy');
});

test("enable", function() {
	ok(false, "missing test - untested code is broken code.");
});

test("disable", function() {
	ok(false, "missing test - untested code is broken code.");
});

})(jQuery);
