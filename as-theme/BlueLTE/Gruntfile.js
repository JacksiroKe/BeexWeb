// BlueLTE Gruntfile
module.exports = function (grunt) { // jshint ignore:line
  'use strict';

  grunt.initConfig({
    pkg   : grunt.file.readJSON('package.json'),
    watch : {
      less : {
        // Compiles less files upon saving
        files: ['build/less/*.less'],
        tasks: ['less:development', 'less:production', 'replace', 'notify:less']
      },
      js   : {
        // Compile js files upon saving
        files: ['build/js/*.js'],
        tasks: ['js', 'notify:js']
      },
      skins: {
        // Compile any skin less files upon saving
        files: ['build/less/skins/*.less'],
        tasks: ['less:skins', 'less:minifiedSkins', 'notify:less']
      }
    },
    // Notify end of tasks
    notify: {
      less: {
        options: {
          title  : 'BlueLTE',
          message: 'LESS finished running'
        }
      },
      js  : {
        options: {
          title  : 'BlueLTE',
          message: 'JS bundler finished running'
        }
      }
    },
    // 'less'-task configuration
    // This task will compile all less files upon saving to create both BlueLTE.css and BlueLTE.min.css
    less  : {
      // Development not compressed
      development  : {
        files: {
          // compilation.css  :  source.less
          'dist/css/BlueLTE.css'                     : 'build/less/BlueLTE.less',
          // BlueLTE without plugins
          'dist/css/alt/BlueLTE-without-plugins.css' : 'build/less/BlueLTE-without-plugins.less',
          // Separate plugins
          'dist/css/alt/BlueLTE-select2.css'         : 'build/less/select2.less',
          'dist/css/alt/BlueLTE-fullcalendar.css'    : 'build/less/fullcalendar.less',
          'dist/css/alt/BlueLTE-bootstrap-social.css': 'build/less/bootstrap-social.less'
        }
      },
      // Production compressed version
      production   : {
        options: {
          compress: true
        },
        files  : {
          // compilation.css  :  source.less
          'dist/css/BlueLTE.min.css'                     : 'build/less/BlueLTE.less',
          // BlueLTE without plugins
          'dist/css/alt/BlueLTE-without-plugins.min.css' : 'build/less/BlueLTE-without-plugins.less',
          // Separate plugins
          'dist/css/alt/BlueLTE-select2.min.css'         : 'build/less/select2.less',
          'dist/css/alt/BlueLTE-fullcalendar.min.css'    : 'build/less/fullcalendar.less',
          'dist/css/alt/BlueLTE-bootstrap-social.min.css': 'build/less/bootstrap-social.less'
        }
      },
      // Non minified skin files
      skins        : {
        files: {
          'dist/css/skins/skin-blue.css'        : 'build/less/skins/skin-blue.less',
          'dist/css/skins/skin-black.css'       : 'build/less/skins/skin-black.less',
          'dist/css/skins/skin-yellow.css'      : 'build/less/skins/skin-yellow.less',
          'dist/css/skins/skin-green.css'       : 'build/less/skins/skin-green.less',
          'dist/css/skins/skin-red.css'         : 'build/less/skins/skin-red.less',
          'dist/css/skins/skin-purple.css'      : 'build/less/skins/skin-purple.less',
          'dist/css/skins/skin-blue-light.css'  : 'build/less/skins/skin-blue-light.less',
          'dist/css/skins/skin-black-light.css' : 'build/less/skins/skin-black-light.less',
          'dist/css/skins/skin-yellow-light.css': 'build/less/skins/skin-yellow-light.less',
          'dist/css/skins/skin-green-light.css' : 'build/less/skins/skin-green-light.less',
          'dist/css/skins/skin-red-light.css'   : 'build/less/skins/skin-red-light.less',
          'dist/css/skins/skin-purple-light.css': 'build/less/skins/skin-purple-light.less',
          'dist/css/skins/_all-skins.css'       : 'build/less/skins/_all-skins.less'
        }
      },
      // Skins minified
      minifiedSkins: {
        options: {
          compress: true
        },
        files  : {
          'dist/css/skins/skin-blue.min.css'        : 'build/less/skins/skin-blue.less',
          'dist/css/skins/skin-black.min.css'       : 'build/less/skins/skin-black.less',
          'dist/css/skins/skin-yellow.min.css'      : 'build/less/skins/skin-yellow.less',
          'dist/css/skins/skin-green.min.css'       : 'build/less/skins/skin-green.less',
          'dist/css/skins/skin-red.min.css'         : 'build/less/skins/skin-red.less',
          'dist/css/skins/skin-purple.min.css'      : 'build/less/skins/skin-purple.less',
          'dist/css/skins/skin-blue-light.min.css'  : 'build/less/skins/skin-blue-light.less',
          'dist/css/skins/skin-black-light.min.css' : 'build/less/skins/skin-black-light.less',
          'dist/css/skins/skin-yellow-light.min.css': 'build/less/skins/skin-yellow-light.less',
          'dist/css/skins/skin-green-light.min.css' : 'build/less/skins/skin-green-light.less',
          'dist/css/skins/skin-red-light.min.css'   : 'build/less/skins/skin-red-light.less',
          'dist/css/skins/skin-purple-light.min.css': 'build/less/skins/skin-purple-light.less',
          'dist/css/skins/_all-skins.min.css'       : 'build/less/skins/_all-skins.less'
        }
      }
    },

    // Uglify task info. Compress the js files.
    uglify: {
      options   : {
        mangle          : true,
        preserveComments: 'some'
      },
      production: {
        files: {
          'dist/js/bluelte.min.js': ['dist/js/bluelte.js']
        }
      }
    },

    // Concatenate JS Files
    concat: {
      options: {
        separator: '\n\n',
        banner   : '/*! BlueLTE app.js\n'
        + '* ================\n'
        + '* Main JS application file for BlueLTE v2. This file\n'
        + '* should be included in all pages. It controls some layout\n'
        + '* options and implements exclusive BlueLTE plugins.\n'
        + '*\n'
        + '* @author Colorlib\n'
        + '* @support <https://github.com/ColorlibHQ/BlueLTE/issues>\n'
        + '* @version <%= pkg.version %>\n'
        + '* @repository <%= pkg.repository.url %>\n'
        + '* @license MIT <http://opensource.org/licenses/MIT>\n'
        + '*/\n\n'
        + '// Make sure jQuery has been loaded\n'
        + 'if (typeof jQuery === \'undefined\') {\n'
        + 'throw new Error(\'BlueLTE requires jQuery\')\n'
        + '}\n\n'
      },
      dist   : {
        src : [
          'build/js/BoxRefresh.js',
          'build/js/BoxWidget.js',
          'build/js/ControlSidebar.js',
          'build/js/DirectChat.js',
          'build/js/PushMenu.js',
          'build/js/TodoList.js',
          'build/js/Tree.js',
          'build/js/Layout.js',
        ],
        dest: 'dist/js/bluelte.js'
      }
    },

    // Replace image paths in BlueLTE without plugins
    replace: {
      withoutPlugins   : {
        src         : ['dist/css/alt/BlueLTE-without-plugins.css'],
        dest        : 'dist/css/alt/BlueLTE-without-plugins.css',
        replacements: [
          {
            from: '../img',
            to  : '../../img'
          }
        ]
      },
      withoutPluginsMin: {
        src         : ['dist/css/alt/BlueLTE-without-plugins.min.css'],
        dest        : 'dist/css/alt/BlueLTE-without-plugins.min.css',
        replacements: [
          {
            from: '../img',
            to  : '../../img'
          }
        ]
      }
    },

    // Build the documentation files
    includes: {
      build: {
        src    : ['*.html'], // Source files
        dest   : 'documentation/', // Destination directory
        flatten: true,
        cwd    : 'documentation/build',
        options: {
          silent     : true,
          includePath: 'documentation/build/include'
        }
      }
    },

    // Optimize images
    image: {
      dynamic: {
        files: [
          {
            expand: true,
            cwd   : 'build/img/',
            src   : ['**/*.{png,jpg,gif,svg,jpeg}'],
            dest  : 'dist/img/'
          }
        ]
      }
    },

    // Validate JS code
    jshint: {
      options: {
        jshintrc: 'build/js/.jshintrc'
      },
      grunt  : {
        options: {
          jshintrc: 'build/grunt/.jshintrc'
        },
        src    : 'Gruntfile.js'
      },
      core   : {
        src: 'build/js/*.js'
      },
      demo   : {
        src: 'dist/js/demo.js'
      },
      pages  : {
        src: 'dist/js/pages/*.js'
      }
    },

    jscs: {
      options: {
        config: 'build/js/.jscsrc'
      },
      core   : {
        src: '<%= jshint.core.src %>'
      },
      pages  : {
        src: '<%= jshint.pages.src %>'
      }
    },

    // Validate CSS files
    csslint: {
      options: {
        csslintrc: 'build/less/.csslintrc'
      },
      dist   : [
        'dist/css/BlueLTE.css'
      ]
    },

    // Validate Bootstrap HTML
    bootlint: {
      options: {
        relaxerror: ['W005']
      },
      files  : ['pages/**/*.html', '*.html']
    },

    // Delete images in build directory
    // After compressing the images in the build/img dir, there is no need
    // for them
    clean: {
      build: ['build/img/*']
    }
  });

  // Load all grunt tasks

  // LESS Compiler
  grunt.loadNpmTasks('grunt-contrib-less');
  // Watch File Changes
  grunt.loadNpmTasks('grunt-contrib-watch');
  // Compress JS Files
  grunt.loadNpmTasks('grunt-contrib-uglify');
  // Include Files Within HTML
  grunt.loadNpmTasks('grunt-includes');
  // Optimize images
  grunt.loadNpmTasks('grunt-image');
  // Validate JS code
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-jscs');
  // Delete not needed files
  grunt.loadNpmTasks('grunt-contrib-clean');
  // Lint CSS
  grunt.loadNpmTasks('grunt-contrib-csslint');
  // Lint Bootstrap
  grunt.loadNpmTasks('grunt-bootlint');
  // Concatenate JS files
  grunt.loadNpmTasks('grunt-contrib-concat');
  // Notify
  grunt.loadNpmTasks('grunt-notify');
  // Replace
  grunt.loadNpmTasks('grunt-text-replace');

  // Linting task
  grunt.registerTask('lint', ['jshint', 'csslint', 'bootlint']);
  // JS task
  grunt.registerTask('js', ['concat', 'uglify']);
  // CSS Task
  grunt.registerTask('css', ['less:development', 'less:production', 'replace']);

  // The default task (running 'grunt' in console) is 'watch'
  grunt.registerTask('default', ['watch']);
};
