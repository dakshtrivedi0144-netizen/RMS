const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const LearningPath = require('../models/LearningPath');
const { Types: { ObjectId } } = require('mongoose');

// @route   GET /api/learning-paths
// @desc    Get all learning paths for the authenticated user
// @access  Private
router.get('/', auth, async (req, res) => {
  try {
    const learningPaths = await LearningPath.find({
      $or: [
        { createdBy: req.user._id },
        { 'enrolledUsers.user': req.user._id },
        { isPublic: true }
      ]
    })
      .populate('createdBy', 'username')
      .sort({ createdAt: -1 });

    // Add progress for each learning path
    const pathsWithProgress = learningPaths.map(path => {
      const pathObj = path.toObject();
      const enrollment = path.enrolledUsers.find(e => e.user.equals(req.user._id));

      if (enrollment) {
        pathObj.progress = enrollment.progress;
        pathObj.completed = enrollment.completed;
        pathObj.lastAccessed = enrollment.lastAccessed;
      } else {
        pathObj.progress = 0;
        pathObj.completed = false;
      }

      return pathObj;
    });

    res.json(pathsWithProgress);
  } catch (err) {
    console.error(err.message);
    res.status(500).send('Server Error');
  }
});

// @route   GET /api/learning-paths/:id
// @desc    Get a single learning path by ID
// @access  Private
router.get('/:id', auth, async (req, res) => {
  try {
    if (!ObjectId.isValid(req.params.id)) {
      return res.status(400).json({ msg: 'Invalid ID format' });
    }

    const learningPath = await LearningPath.findOne({
      _id: req.params.id,
      $or: [
        { createdBy: req.user._id },
        { 'enrolledUsers.user': req.user._id },
        { isPublic: true }
      ]
    }).populate('createdBy', 'username');

    if (!learningPath) {
      return res.status(404).json({ msg: 'Learning path not found' });
    }

    // Check if user is enrolled, if not, enroll them
    const isEnrolled = learningPath.enrolledUsers.some(e => e.user.equals(req.user._id));

    if (!isEnrolled && !learningPath.createdBy.equals(req.user._id)) {
      learningPath.enrolledUsers.push({
        user: req.user._id,
        progress: 0,
        completed: false,
        lastAccessed: new Date()
      });
      await learningPath.save();
    } else if (isEnrolled) {
      // Update last accessed time
      await LearningPath.updateOne(
        { _id: req.params.id, 'enrolledUsers.user': req.user._id },
        { $set: { 'enrolledUsers.$.lastAccessed': new Date() } }
      );
    }

    // Calculate progress
    const enrollment = learningPath.enrolledUsers.find(e => e.user.equals(req.user._id));
    const progress = enrollment ? enrollment.progress : 0;
    const completed = enrollment ? enrollment.completed : false;

    const pathObj = learningPath.toObject();
    pathObj.progress = progress;
    pathObj.completed = completed;

    res.json(pathObj);
  } catch (err) {
    console.error(err.message);
    if (err.kind === 'ObjectId') {
      return res.status(404).json({ msg: 'Learning path not found' });
    }
    res.status(500).send('Server Error');
  }
});

// @route   POST /api/learning-paths
// @desc    Create a new learning path
// @access  Private
router.post('/', auth, async (req, res) => {
  try {
    const { title, description, category, difficulty, modules, isPublic } = req.body;

    const newPath = new LearningPath({
      title,
      description,
      category,
      difficulty: difficulty || 'beginner',
      modules: modules || [],
      createdBy: req.user._id,
      isPublic: isPublic || false,
      enrolledUsers: [{
        user: req.user._id,
        progress: 0,
        completed: false,
        lastAccessed: new Date()
      }]
    });

    const learningPath = await newPath.save();
    res.json(learningPath);
  } catch (err) {
    console.error(err.message);
    res.status(500).send('Server Error');
  }
});

// @route   PUT /api/learning-paths/:id
// @desc    Update a learning path
// @access  Private
router.put('/:id', auth, async (req, res) => {
  try {
    if (!ObjectId.isValid(req.params.id)) {
      return res.status(400).json({ msg: 'Invalid ID format' });
    }

    const { title, description, category, difficulty, modules, isPublic } = req.body;

    let learningPath = await LearningPath.findById(req.params.id);

    if (!learningPath) {
      return res.status(404).json({ msg: 'Learning path not found' });
    }

    // Check if user owns the learning path
    if (!learningPath.createdBy.equals(req.user._id)) {
      return res.status(401).json({ msg: 'User not authorized' });
    }

    // Update fields
    learningPath.title = title || learningPath.title;
    learningPath.description = description || learningPath.description;
    learningPath.category = category || learningPath.category;
    learningPath.difficulty = difficulty || learningPath.difficulty;
    learningPath.modules = modules || learningPath.modules;
    learningPath.isPublic = isPublic !== undefined ? isPublic : learningPath.isPublic;

    await learningPath.save();
    res.json(learningPath);
  } catch (err) {
    console.error(err.message);
    res.status(500).send('Server Error');
  }
});

// @route   DELETE /api/learning-paths/:id
// @desc    Delete a learning path
// @access  Private
router.delete('/:id', auth, async (req, res) => {
  try {
    if (!ObjectId.isValid(req.params.id)) {
      return res.status(400).json({ msg: 'Invalid ID format' });
    }

    const learningPath = await LearningPath.findById(req.params.id);

    if (!learningPath) {
      return res.status(404).json({ msg: 'Learning path not found' });
    }

    // Check if user owns the learning path
    if (!learningPath.createdBy.equals(req.user._id)) {
      return res.status(401).json({ msg: 'User not authorized' });
    }

    await learningPath.deleteOne();
    res.json({ msg: 'Learning path removed' });
  } catch (err) {
    console.error(err.message);
    if (err.kind === 'ObjectId') {
      return res.status(404).json({ msg: 'Learning path not found' });
    }
    res.status(500).send('Server Error');
  }
});

// @route   PUT /api/learning-paths/:id/progress
// @desc    Update user progress on a learning path
// @access  Private
router.put('/:id/progress', auth, async (req, res) => {
  try {
    if (!ObjectId.isValid(req.params.id)) {
      return res.status(400).json({ msg: 'Invalid ID format' });
    }

    const { moduleIndex, resourceIndex, completed } = req.body;

    if (typeof moduleIndex === 'undefined' || typeof resourceIndex === 'undefined' || typeof completed === 'undefined') {
      return res.status(400).json({ msg: 'Module index, resource index, and completion status are required' });
    }

    const learningPath = await LearningPath.findOne({
      _id: req.params.id,
      'enrolledUsers.user': req.user._id
    });

    if (!learningPath) {
      return res.status(404).json({ msg: 'Learning path not found or you are not enrolled' });
    }

    // Update resource completion status
    if (learningPath.modules[moduleIndex] && learningPath.modules[moduleIndex].resources[resourceIndex]) {
      learningPath.modules[moduleIndex].resources[resourceIndex].completed = completed;
    } else {
      return res.status(400).json({ msg: 'Invalid module or resource index' });
    }

    // Calculate progress
    const totalResources = learningPath.modules.reduce(
      (total, module) => total + module.resources.length, 0
    );

    const completedResources = learningPath.modules.reduce((total, module) => {
      return total + module.resources.filter(r => r.completed).length;
    }, 0);

    const progress = totalResources === 0 ? 0 : Math.round((completedResources / totalResources) * 100);
    const isCompleted = progress === 100;

    // Update enrollment progress
    const enrollmentIndex = learningPath.enrolledUsers.findIndex(e => e.user.equals(req.user._id));
    if (enrollmentIndex !== -1) {
      learningPath.enrolledUsers[enrollmentIndex].progress = progress;
      learningPath.enrolledUsers[enrollmentIndex].completed = isCompleted;
      learningPath.enrolledUsers[enrollmentIndex].lastAccessed = new Date();
    }

    await learningPath.save();

    res.json({
      progress,
      completed: isCompleted,
      message: 'Progress updated successfully'
    });
  } catch (err) {
    console.error(err.message);
    res.status(500).send('Server Error');
  }
});

// @route   POST /api/learning-paths/:id/like
// @desc    Like or unlike a learning path
// @access  Private
router.post('/:id/like', auth, async (req, res) => {
  try {
    if (!ObjectId.isValid(req.params.id)) {
      return res.status(400).json({ msg: 'Invalid ID format' });
    }

    const learningPath = await LearningPath.findById(req.params.id);

    if (!learningPath) {
      return res.status(404).json({ msg: 'Learning path not found' });
    }

    // Check if the post has already been liked
    if (learningPath.likes.some(like => like.user.toString() === req.user.id)) {
      // Unlike the post
      learningPath.likes = learningPath.likes.filter(
        like => like.user.toString() !== req.user.id
      );
    } else {
      // Like the post
      learningPath.likes.unshift({ user: req.user.id });
    }

    await learningPath.save();
    res.json(learningPath.likes);
  } catch (err) {
    console.error(err.message);
    res.status(500).send('Server Error');
  }
});

module.exports = router;
