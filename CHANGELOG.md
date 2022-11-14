# Craft Contact Form Extensions Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [4.2.2] - 2022-11-14
Fixed an issue where the settings tabs were not showing [#164](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/164)

## [4.2.1] - 2022-11-02
Fixed an issue where you couldn't update the Recaptcha settings in the Dashboard. [#162](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/162)

## [4.2.0] - 2022-09-12
Release beta label. Thank you all for helping us test this out so thoroughly! You all rock \m/!

## [4.0.0-beta.3] - 2022-05-16
There were many updates made for this plugin and cleaned up and adjusted many things. The 4 changes below will need to be updated in the templates in order for everything to work.

```
message['template'] -> message['confirmationTemplate']
message['subject'] -> message['confirmationSubject']
message['recaptchaTemplateOverride'] -> message['disableRecaptcha']
message['saveSubmissionOverride'] -> message['disableSaveSubmission']
```

## [1.3.0] - 2022-04-14
- Adding ability to override recaptcha on a per form basis. [#108](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/108)
- Override for saving to db on a per form basis
- Add ability to disable the email confirmation should be sent on a specific form. 

## [1.2.8] - 2021-10-19
- Fixed Second form on page does not send [#78](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/78) - Added unique id's to the functions and id tags to make the recaptcha separate for each form.
- Can the recaptcha script be defer / async? [#100](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/100) - Added a callback function off the async script loading that keeps everything loading together correctly.
- Use of recaptcha.net instead of google.com [#113](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/113) - Added settings and ability to override the API URL and Verification API URL.

## [1.2.7] - 2021-10-13
- Update readme for additional clarity on reCaptcha placement. Cheers to [@jamiematrix](https://github.com/jamiematrix) for the [assist](https://github.com/hybridinteractive/craft-contact-form-extensions/pull/124)!

## [1.2.6] - 2021-09-27
- Add overwrite field for the notification template. Thanks go to [@mathiashelin](https://github.com/mathiashelin) for the [PR](https://github.com/hybridinteractive/craft-contact-form-extensions/pull/122)!

## [1.2.5] - 2021-06-27
- Fixed a bug that would cause the Google ReCaptcha to time out after two minutes. HUGE shout out to [@danielratzinger](https://github.com/danielratzinger) for the PR!
- For more information on this update, take a look at the [pull request #101](https://github.com/hybridinteractive/craft-contact-form-extensions/pull/101)

## [1.2.4] - 2020-10-23
- Fixed a bug that was causing `lightSwitch` fields to be out of sync [#87](https://github.com/hybridinteractive/craft-contact-form-extensions/issues/87)

## [1.2.3] - 2020-10-23
- Added `Subject` to Submissions Detail page. 

## [1.2.2] - 2020-08-28
- Added `fromName` for confirmation email - Thank you [@skoften](https://github.com/skoften) and [@jesuismaxime](https://github.com/jesuismaxime)
- Update: Set template mode to CP only if a CP request - A big thank you to [@bencroker](https://github.com/bencroker) for the PR!

## [1.2.1] - 2020-05-14
- Began migrating plugin to Hybrid Interactive

## [1.2.0] - 2019-09-06
- Ability to override the confirmation subject per form

## [1.1.7] - 2019-08-09
- Added Dutch translations
- Fixed a bug causing the Confirmation message sending to fail when using environment variables

## [1.1.6] - 2019-05-08
### Fixed
- Fixed array key check
- Added Norwegian translations

## [1.1.5] - 2019-04-15
### Fixed
- Finally fix the element index UTF8 issue.

## [1.1.4] - 2019-04-01
### Fixed
- Fix utf8 issue

## [1.1.3] - 2019-04-01
### Fixed
- Fix overriding `toEmail`

## [1.1.2] - 2019-04-01
### Added
- Ability to override `toEmail` inside your form
- Ability to override `template` inside your form (thanks @helderdb)
- Ability to hide the badge when using ReCaptcha V3 

### Fixed
- Fixed an issue with email being sent from the wrong address (thanks @helderdb)

## [1.1.1] - 2019-02-05
### Added
- Added support for ReCaptcha V3

## [1.1.0] - 2019-02-05
### Added
- Added support for Craft 3.1 env vars (thanks @benface)

## 1.0.13 - 2019-01-18
### Fixed
- Fixed an issue with project config in 3.1

## 1.0.12 - 2018-12-03
### Fixed
- Contact Form Extensions now checks if the submission was marked as spam before handling anything itself. This might solve issues with other plugins that mark it first.

### Added
- Added a `craft.contactForms.submissions` variable to display submissions in your templates (thanks @Floriswijgergangs)

## 1.0.11 - 2018-09-26
### Fixed
- Confirmation email sender shoul be the system admin, thanks @Pinchcliffe

## 1.0.10 - 2018-08-21
### Fixed
- Fixed encoding errors when saving checkboxes
- Fixed issue with customizing table attributes
- Fixed display of messages with new lines
- Fixed display of badge position setting

## 1.0.9 - 2018-08-03
### Fixed
- Fixed a bug with encoding caused by the previous update

## 1.0.8 - 2018-07-31
### Fixed
- UTF8 encoding issues should now be fixed for new submissions
- The columns added by the "Upvote" plugin won't show anymore

## 1.0.7 - 2018-07-13
### Fixed
- "All submissions" can now be translated

## 1.0.6 - 2018-06-07
### Fixed
- The badge position setting now saves the correct values
- Added a note when hiding the badge you should inform users in a different way
- Fix saving checkbox and radio inputs

## 1.0.5 - 2018-06-01
### Added
- You can now add an invisible reCAPTCHA to your forms.

## 1.0.4 - 2018-05-22
### Added
- You can now change the form name by passing a `message[formName]` field in your form. This way the entries will be grouped by each form. Thanks @curtishenson

## 1.0.3 - 2018-05-14
### Fixed
- Fixed a bug where the confirmation email was not sent to the person filling out the form.

## 1.0.2 - 2018-05-10
### Fixed
- Don't show the nav item when database submissions are disabled

## 1.0.1 - 2018-05-08
### Fixed
- Fixed a bug where the submission was being passed JSON encoded to Twig

## 1.0.0 - 2018-05-06
### Added
- Initial release
