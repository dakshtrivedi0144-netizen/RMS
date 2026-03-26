const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const { generateLearningPath, getResourceRecommendations, improveLearningPath } = require('../services/aiService');
const LearningPath = require('../models/LearningPath');

// @route   POST /api/ai/generate-path
// @desc    Generate a personalized learning path using AI
// @access  Private
router.post('/generate-path', auth, async (req, res) => {
  try {
    const { topic, preferences } = req.body;

    if (!topic) {
      return res.status(400).json({ msg: 'Topic is required' });
    }

    const learningPath = await generateLearningPath(req.user, topic, preferences);
    res.json(learningPath);
  } catch (err) {
    console.error('Error in /api/ai/generate-path:', err.message);
    res.status(500).json({ msg: 'Server error', error: err.message });
  }
});

// @route   GET /api/ai/recommend-resources
// @desc    Get AI-recommended resources
// @access  Private
router.get('/recommend-resources', auth, async (req, res) => {
  try {
    const { topic } = req.query;

    if (!topic) {
      return res.status(400).json({ msg: 'Topic is required' });
    }

    // Get user's completed resources to avoid duplicates
    const userLearningPaths = await LearningPath.find({ 'enrolledUsers.user': req.user._id });
    const completedResources = [];

    userLearningPaths.forEach(path => {
      path.modules.forEach(module => {
        module.resources.forEach(resource => {
          if (resource.completed) {
            completedResources.push(resource.title);
          }
        });
      });
    });

    const recommendations = await getResourceRecommendations(topic, completedResources);
    res.json(recommendations);
  } catch (err) {
    console.error('Error in /api/ai/recommend-resources:', err.message);
    res.status(500).json({ msg: 'Server error', error: err.message });
  }
});

// @route   POST /api/ai/improve-path
// @desc    Get AI suggestions to improve an existing learning path
// @access  Private
router.post('/improve-path', auth, async (req, res) => {
  try {
    const { pathId, feedback } = req.body;

    const learningPath = await LearningPath.findOne({
      _id: pathId,
      'enrolledUsers.user': req.user._id
    });

    if (!learningPath) {
      return res.status(404).json({ msg: 'Learning path not found' });
    }

    const suggestions = await improveLearningPath(learningPath, feedback);
    res.json(suggestions);
  } catch (err) {
    console.error('Error in /api/ai/improve-path:', err.message);
    res.status(500).json({ msg: 'Server error', error: err.message });
  }
});

module.exports = router;
